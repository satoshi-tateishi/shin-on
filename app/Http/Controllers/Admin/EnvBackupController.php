<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DropboxService;
use App\Services\EnvEncryptionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EnvBackupController extends Controller
{
    private EnvEncryptionService $encryptor;
    private DropboxService $dropboxService;

    public function __construct(EnvEncryptionService $encryptor, DropboxService $dropboxService)
    {
        $this->encryptor = $encryptor;
        $this->dropboxService = $dropboxService;
    }

    private function checkAdminAccess(): void
    {
        if (!auth()->user() || auth()->user()->role !== 'admin') {
            abort(403, 'Admin access required');
        }
    }

    /**
     * 環境設定バックアップの作成
     */
    public function backupEnv(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $request->validate([
                'password' => 'required|string|min:12',
                'skip_encryption' => 'boolean',
            ]);

            // パスワードをリクエストから即座に取得して削除
            $password = $request->input('password');
            $skipEncryption = $request->boolean('skip_encryption', false);
            $request->replace($request->except(['password']));

            // Dropbox認証チェック
            if (!$this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            // .envファイル存在チェック
            $envPath = base_path('.env');
            if (!file_exists($envPath)) {
                return response()->json([
                    'success' => false,
                    'error' => '.envファイルが見つかりません',
                ], 404);
            }

            // 暗号化なしの場合の警告
            if ($skipEncryption) {
                Log::warning('Environment backup created without encryption', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()->email,
                ]);
            }

            // .envファイル読み込み
            $plaintext = file_get_contents($envPath);

            // 暗号化処理
            if (!$skipEncryption) {
                // パスワード強度チェック
                $errors = $this->encryptor->validatePasswordStrength($password);
                if (!empty($errors)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'パスワードが要件を満たしていません',
                        'password_errors' => $errors,
                    ], 400);
                }

                $package = $this->encryptor->encrypt($plaintext, $password);
                $content = json_encode($package, JSON_PRETTY_PRINT);
                $extension = '.json';
                $prefix = 'env_encrypted';
            } else {
                $content = $plaintext;
                $extension = '.txt';
                $prefix = 'env_plain';
            }

            // ファイル名生成
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "{$prefix}_{$timestamp}{$extension}";

            // 一時ファイル作成
            $tempDir = storage_path('app/temp');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $tempPath = "{$tempDir}/{$filename}";
            file_put_contents($tempPath, $content);

            // Dropboxにアップロード
            $remotePath = "/env_secure/{$filename}";
            $uploadResult = $this->dropboxService->uploadFile($tempPath, $remotePath);

            if ($uploadResult) {
                // 一時ファイル削除
                unlink($tempPath);

                Log::info('Environment backup created via web UI', [
                    'user_id' => auth()->id(),
                    'filename' => $filename,
                    'size' => strlen($content),
                    'encrypted' => !$skipEncryption,
                    'remote_path' => $remotePath,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '環境設定のバックアップが完了しました',
                    'filename' => $filename,
                    'size' => strlen($content),
                    'encrypted' => !$skipEncryption,
                    'timestamp' => $timestamp,
                ]);

            } else {
                throw new Exception('Dropboxアップロードに失敗しました');
            }

        } catch (Exception $e) {
            Log::error('Environment backup failed via web UI', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            // 一時ファイルのクリーンアップ
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            return response()->json([
                'success' => false,
                'error' => '環境設定バックアップに失敗しました: ' . $e->getMessage(),
            ], 500);

        } finally {
            // メモリからパスワードをクリア
            if (isset($password)) {
                unset($password);
            }
        }
    }

    /**
     * 環境設定バックアップ一覧の取得
     */
    public function listEnvBackups(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            if (!$this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            $contents = $this->dropboxService->listFolder('/env_secure');
            $backups = $contents['entries'] ?? [];

            // バックアップリストを整形
            $formattedBackups = array_map(function ($backup) {
                return [
                    'name' => $backup['name'],
                    'path' => $backup['path_display'],
                    'size' => $backup['size'] ?? 0,
                    'size_formatted' => $this->formatFileSize($backup['size'] ?? 0),
                    'modified' => $backup['server_modified'] ?? null,
                    'modified_formatted' => isset($backup['server_modified']) ?
                        date('Y-m-d H:i:s', strtotime($backup['server_modified'])) : null,
                    'encrypted' => str_contains($backup['name'], 'encrypted'),
                    'type' => str_contains($backup['name'], 'encrypted') ? 'encrypted' : 'plain',
                ];
            }, $backups);

            // 新しい順でソート
            usort($formattedBackups, function ($a, $b) {
                return strcmp($b['name'], $a['name']);
            });

            return response()->json([
                'success' => true,
                'backups' => $formattedBackups,
                'count' => count($formattedBackups),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to list environment backups via web UI', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '環境設定バックアップ一覧の取得に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 環境設定の復元
     */
    public function restoreEnv(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $request->validate([
                'backup_file' => 'required|string',
                'password' => 'required_if:is_encrypted,true|string',
                'is_encrypted' => 'boolean',
                'preview_only' => 'boolean',
            ]);

            $backupFile = $request->input('backup_file');
            $password = $request->input('password');
            $isEncrypted = $request->boolean('is_encrypted', true);
            $previewOnly = $request->boolean('preview_only', false);

            // 試行回数制限チェック
            $attempts = Cache::get("env_restore_attempts_" . auth()->id(), 0);
            if ($attempts >= 3) {
                $lockUntil = Cache::get("env_restore_locked_until_" . auth()->id());
                if ($lockUntil && now()->lessThan($lockUntil)) {
                    return response()->json([
                        'success' => false,
                        'error' => '試行回数制限に達しました。しばらく待ってからお試しください。',
                    ], 429);
                }
                // ロック期間終了時にリセット
                Cache::forget("env_restore_attempts_" . auth()->id());
                Cache::forget("env_restore_locked_until_" . auth()->id());
            }

            if (!$this->dropboxService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dropbox認証が必要です',
                ], 400);
            }

            // バックアップファイルをダウンロード
            $content = $this->dropboxService->downloadFile($backupFile);

            // 復号化
            if ($isEncrypted) {
                try {
                    $package = json_decode($content, true);
                    if (!$package) {
                        throw new Exception('Invalid JSON format in backup file');
                    }

                    $decrypted = $this->encryptor->decrypt($package, $password);
                    $envContent = $decrypted;

                    // 成功時は試行回数をリセット
                    Cache::forget("env_restore_attempts_" . auth()->id());

                } catch (Exception $e) {
                    // 失敗時は試行回数を増加
                    $newAttempts = $attempts + 1;
                    Cache::put("env_restore_attempts_" . auth()->id(), $newAttempts, now()->addHour());

                    if ($newAttempts >= 3) {
                        Cache::put("env_restore_locked_until_" . auth()->id(), now()->addMinutes(30), now()->addHour());
                    }

                    return response()->json([
                        'success' => false,
                        'error' => 'パスワードが正しくないか、データが破損しています',
                        'remaining_attempts' => max(0, 3 - $newAttempts),
                    ], 400);
                }
            } else {
                $envContent = $content;
            }

            // 環境設定のプレビュー生成
            $preview = $this->generateEnvPreview($envContent);

            // プレビューのみの場合
            if ($previewOnly) {
                return response()->json([
                    'success' => true,
                    'preview' => $preview,
                    'message' => 'プレビューを生成しました（変更は適用されていません）',
                ]);
            }

            // 新しい設定を適用
            $this->applyConfiguration($envContent);

            Log::info('Environment restored via web UI', [
                'user_id' => auth()->id(),
                'backup_file' => $backupFile,
                'encrypted' => $isEncrypted,
                'size' => strlen($envContent),
            ]);

            return response()->json([
                'success' => true,
                'message' => '環境設定の復元が完了しました',
                'preview' => $preview,
            ]);

        } catch (Exception $e) {
            Log::error('Environment restore failed via web UI', [
                'user_id' => auth()->id(),
                'backup_file' => $request->input('backup_file'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '環境設定の復元に失敗しました: ' . $e->getMessage(),
            ], 500);

        } finally {
            // メモリからパスワードをクリア
            if (isset($password)) {
                unset($password);
            }
        }
    }

    /**
     * パスワード強度の検証
     */
    public function validatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $password = $request->input('password');
        $errors = $this->encryptor->validatePasswordStrength($password);
        $score = $this->encryptor->calculatePasswordScore($password);

        return response()->json([
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => $score,
            'strength' => $this->getPasswordStrengthText($score),
        ]);
    }

    private function generateEnvPreview(string $content): array
    {
        $lines = explode("\n", $content);
        $preview = [];
        $secretCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^([A-Z_]+)=(.*)/', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];

                // 機密情報をマスク
                $isSecret = str_contains(strtolower($key), 'password') ||
                           str_contains(strtolower($key), 'secret') ||
                           str_contains(strtolower($key), 'key') ||
                           str_contains(strtolower($key), 'token') ||
                           str_starts_with($key, 'LINEWORKS_') ||
                           str_starts_with($key, 'DROPBOX_');

                if ($isSecret) {
                    $value = '***MASKED***';
                    $secretCount++;
                }

                $preview[] = [
                    'key' => $key,
                    'value' => substr($value, 0, 50) . (strlen($value) > 50 ? '...' : ''),
                    'is_secret' => $isSecret,
                ];
            }
        }

        return [
            'entries' => $preview,
            'total_count' => count($preview),
            'secret_count' => $secretCount,
        ];
    }

    private function applyConfiguration(string $content): void
    {
        $envPath = base_path('.env');

        // 書き込み権限チェック
        if (file_exists($envPath) && !is_writable($envPath)) {
            throw new Exception('.env file is not writable');
        }

        file_put_contents($envPath, $content);
        chmod($envPath, 0600);
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    private function getPasswordStrengthText(int $score): string
    {
        if ($score >= 90) return 'Very Strong';
        if ($score >= 80) return 'Strong';
        if ($score >= 70) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 40) return 'Weak';
        return 'Very Weak';
    }
}