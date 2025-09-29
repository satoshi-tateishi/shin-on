<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Services\DropboxService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BackupController extends Controller
{
    private BackupService $backupService;

    private DropboxService $dropboxService;

    public function __construct(BackupService $backupService, DropboxService $dropboxService)
    {
        $this->backupService = $backupService;
        $this->dropboxService = $dropboxService;
    }

    private function checkAdminAccess(): void
    {
        if (! auth()->user() || auth()->user()->role !== 'admin') {
            abort(403, 'Admin access required');
        }
    }

    public function index(): View
    {
        $this->checkAdminAccess();

        $isAuthenticated = $this->dropboxService->isAuthenticated();
        $authStatus = null;

        if ($isAuthenticated) {
            try {
                $authStatus = $this->getAuthStatusData();
            } catch (Exception $e) {
                Log::error('Failed to get Dropbox auth status', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('admin.backup.index', [
            'isAuthenticated' => $isAuthenticated,
            'authStatus' => $authStatus,
        ]);
    }

    public function runBackup(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $request->validate([
                'upload_to_dropbox' => 'boolean',
            ]);

            $uploadToDropbox = $request->boolean('upload_to_dropbox', true);

            if ($uploadToDropbox && ! $this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です。まず認証を完了してください。',
                ], 400);
            }

            Log::info('Starting backup from admin panel', [
                'user_id' => auth()->id(),
                'upload_to_dropbox' => $uploadToDropbox,
            ]);

            $result = $this->backupService->createFullBackup($uploadToDropbox);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'バックアップが正常に完了しました',
                    'timestamp' => $result['timestamp'],
                    'results' => $result['results'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'results' => $result['results'] ?? [],
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Backup failed from admin panel', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'バックアップ処理中にエラーが発生しました: '.$e->getMessage(),
            ], 500);
        }
    }

    public function listBackups(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            if (! $this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            $result = $this->backupService->listBackups();

            return response()->json($result);

        } catch (Exception $e) {
            Log::error('Failed to list backups from admin panel', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'バックアップ一覧の取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    public function testConnection(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            if (! $this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            $connectionTest = $this->dropboxService->testConnection();
            $tokenInfo = $this->dropboxService->getTokenInfo();

            return response()->json([
                'success' => true,
                'account_info' => $connectionTest,
                'token_info' => $tokenInfo,
                'message' => '接続テストが成功しました',
            ]);

        } catch (Exception $e) {
            Log::error('Connection test failed from admin panel', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '接続テストに失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    public function refreshToken(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            if (! $this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            // トークンを強制リフレッシュ
            $result = $this->dropboxService->forceRefreshToken();

            if ($result) {
                $tokenInfo = $this->dropboxService->getTokenInfo();

                return response()->json([
                    'success' => true,
                    'message' => 'トークンが正常に更新されました',
                    'token_info' => $tokenInfo,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'トークンの更新に失敗しました',
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Token refresh failed from admin panel', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'トークン更新中にエラーが発生しました: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getRestorableBackups(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            if (! $this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            $result = $this->backupService->getRestorableBackups();

            return response()->json($result);

        } catch (Exception $e) {
            Log::error('Failed to get restorable backups from admin panel', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '復元可能なバックアップの取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadBackup(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $request->validate([
                'timestamp' => 'required|string|regex:/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/',
            ]);

            $timestamp = $request->input('timestamp');

            if (! $this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            Log::info('Starting backup download from admin panel', [
                'user_id' => auth()->id(),
                'timestamp' => $timestamp,
            ]);

            $result = $this->backupService->downloadBackupFromDropbox($timestamp);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'バックアップのダウンロードが完了しました',
                    'download_info' => $result,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Backup download failed from admin panel', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'バックアップのダウンロードに失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    public function restoreDatabase(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $request->validate([
                'timestamp' => 'required|string|regex:/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/',
                'create_backup_first' => 'boolean',
            ]);

            $timestamp = $request->input('timestamp');
            $createBackupFirst = $request->boolean('create_backup_first', true);

            if (! $this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            Log::info('Starting database restore from admin panel', [
                'user_id' => auth()->id(),
                'timestamp' => $timestamp,
                'create_backup_first' => $createBackupFirst,
            ]);

            // まずバックアップをダウンロード
            $downloadResult = $this->backupService->downloadBackupFromDropbox($timestamp);

            if (! $downloadResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'バックアップのダウンロードに失敗しました: '.$downloadResult['error'],
                ], 500);
            }

            // データベースファイルを探す
            $databaseFile = null;
            foreach ($downloadResult['files'] as $file) {
                if ($file['type'] === 'database') {
                    $databaseFile = $file['local_path'];
                    break;
                }
            }

            if (! $databaseFile) {
                return response()->json([
                    'success' => false,
                    'error' => 'バックアップにデータベースファイルが含まれていません',
                ], 400);
            }

            // データベース復元を実行
            $restoreResult = $this->backupService->restoreDatabase($databaseFile, $createBackupFirst);

            // 復元後にファイルをクリーンアップ
            $this->backupService->cleanupRestoreFiles($timestamp);

            if ($restoreResult['success']) {
                Log::info('Database restore completed from admin panel', [
                    'user_id' => auth()->id(),
                    'timestamp' => $timestamp,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'データベースの復元が完了しました',
                    'restore_info' => $restoreResult,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $restoreResult['error'],
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Database restore failed from admin panel', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'データベースの復元に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    public function validateRestore(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $request->validate([
                'timestamp' => 'required|string|regex:/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/',
            ]);

            $timestamp = $request->input('timestamp');

            if (! $this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            // バックアップ一覧から指定されたバックアップを検索
            $backupsResult = $this->backupService->getRestorableBackups();

            if (! $backupsResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $backupsResult['error'],
                ], 500);
            }

            $targetBackup = null;
            foreach ($backupsResult['backups'] as $backup) {
                if ($backup['name'] === $timestamp) {
                    $targetBackup = $backup;
                    break;
                }
            }

            if (! $targetBackup) {
                return response()->json([
                    'success' => false,
                    'error' => 'バックアップが見つかりません',
                ], 404);
            }

            // データベースファイルの存在確認
            $hasDatabaseFile = false;
            foreach ($targetBackup['files'] as $file) {
                if ($file['type'] === 'database') {
                    $hasDatabaseFile = true;
                    break;
                }
            }

            return response()->json([
                'success' => true,
                'backup_info' => $targetBackup,
                'can_restore_database' => $hasDatabaseFile,
                'warnings' => [
                    '復元処理は現在のデータを完全に置き換えます',
                    '復元前に自動バックアップを作成することを強く推奨します',
                    '復元中はシステムが一時的に利用できなくなります',
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Restore validation failed from admin panel', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '復元の検証に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    private function getAuthStatusData(): array
    {
        try {
            $connectionTest = $this->dropboxService->testConnection();
            $tokenInfo = $this->dropboxService->getTokenInfo();

            return [
                'success' => true,
                'account_info' => $connectionTest,
                'token_info' => $tokenInfo,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
