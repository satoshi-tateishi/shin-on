<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Services\DropboxService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    private function getAuthStatusData(): array
    {
        try {
            $connectionTest = $this->dropboxService->testConnection();

            return [
                'success' => true,
                'account_info' => $connectionTest,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
