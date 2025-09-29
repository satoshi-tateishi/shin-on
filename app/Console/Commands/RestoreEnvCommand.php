<?php

namespace App\Console\Commands;

use App\Services\DropboxService;
use App\Services\EnvEncryptionService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestoreEnvCommand extends Command
{
    protected $signature = 'restore:env
        {--file= : Specific backup file to restore}
        {--list : List available env backups}
        {--dry-run : Preview configuration without applying}
        {--no-backup : Skip creating backup of current .env}';

    protected $description = 'Restore .env file from encrypted Dropbox backup';

    private EnvEncryptionService $encryptor;
    private DropboxService $dropbox;

    public function __construct(EnvEncryptionService $encryptor, DropboxService $dropbox)
    {
        parent::__construct();
        $this->encryptor = $encryptor;
        $this->dropbox = $dropbox;
    }

    public function handle(): int
    {
        $this->info('🔐 Environment File Restore Tool');
        $this->info('=================================');

        // Dropbox認証チェック
        if (!$this->dropbox->isAuthenticated()) {
            $this->error('❌ Dropbox authentication required');
            $this->info('Please authenticate at: /admin/backup');
            return 1;
        }

        // バックアップ一覧表示
        if ($this->option('list')) {
            return $this->listBackups();
        }

        // バックアップファイル選択
        $backupFile = $this->selectBackupFile();
        if (!$backupFile) {
            return 1;
        }

        try {
            // バックアップファイルをダウンロード
            $this->info("📥 Downloading: {$backupFile['name']}");
            $content = $this->dropbox->downloadFile($backupFile['path']);

            // ファイルタイプ判定
            $isEncrypted = str_contains($backupFile['name'], 'encrypted') ||
                          str_ends_with($backupFile['name'], '.json');

            if ($isEncrypted) {
                // 暗号化ファイルの復号化
                $decrypted = $this->decryptBackup($content);
                if (!$decrypted) {
                    return 1;
                }
            } else {
                // 平文ファイル
                $this->warn('⚠️  This backup is not encrypted');
                $decrypted = $content;
            }

            // プレビュー表示
            $this->displayEnvironmentPreview($decrypted, $backupFile);

            // ドライランモード
            if ($this->option('dry-run')) {
                $this->info('✅ Dry-run mode - no changes applied');
                return 0;
            }

            // 確認
            if (!$this->confirm('Apply this configuration to .env?')) {
                $this->info('Operation cancelled');
                return 0;
            }

            // 現在の.envをバックアップ
            if (!$this->option('no-backup')) {
                $this->backupCurrentEnv();
            }

            // 新しい設定を適用
            $this->applyConfiguration($decrypted);

            $this->info('✅ Environment restored successfully');

            // アプリケーション再起動の推奨
            $this->warn('⚠️  Consider restarting the application to apply all changes');

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Restore failed: ' . $e->getMessage());

            Log::error('Environment restore failed via CLI', [
                'backup_file' => $backupFile['name'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    private function listBackups(): int
    {
        try {
            $this->info('📁 Listing environment backups...');

            $contents = $this->dropbox->listFolder('/env_secure');
            $backups = $contents['entries'] ?? [];

            if (empty($backups)) {
                $this->info('No environment backups found');
                return 0;
            }

            // バックアップリストをソート（新しい順）
            usort($backups, function ($a, $b) {
                return strcmp($b['name'], $a['name']);
            });

            $tableData = array_map(function ($backup) {
                $size = isset($backup['size']) ? $this->formatFileSize($backup['size']) : 'Unknown';
                $modified = isset($backup['server_modified']) ?
                    date('Y-m-d H:i:s', strtotime($backup['server_modified'])) : 'Unknown';
                $type = str_contains($backup['name'], 'encrypted') ? '🔒 Encrypted' : '📄 Plain text';

                return [
                    $backup['name'],
                    $size,
                    $modified,
                    $type
                ];
            }, $backups);

            $this->table(['File', 'Size', 'Modified', 'Type'], $tableData);

            $this->info("Found " . count($backups) . " environment backup(s)");

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Failed to list backups: ' . $e->getMessage());
            return 1;
        }
    }

    private function selectBackupFile(): ?array
    {
        $specifiedFile = $this->option('file');

        try {
            $contents = $this->dropbox->listFolder('/env_secure');
            $backups = $contents['entries'] ?? [];

            if (empty($backups)) {
                $this->error('❌ No environment backups found');
                return null;
            }

            // バックアップリストをソート（新しい順）
            usort($backups, function ($a, $b) {
                return strcmp($b['name'], $a['name']);
            });

            // 特定ファイルが指定されている場合
            if ($specifiedFile) {
                foreach ($backups as $backup) {
                    if ($backup['name'] === $specifiedFile ||
                        str_contains($backup['name'], $specifiedFile)) {
                        return [
                            'name' => $backup['name'],
                            'path' => $backup['path_display'],
                            'size' => $backup['size'] ?? 0
                        ];
                    }
                }
                $this->error("❌ Backup file '{$specifiedFile}' not found");
                return null;
            }

            // 対話式選択
            $this->info('📁 Available environment backups:');

            $choices = [];
            foreach ($backups as $index => $backup) {
                $size = isset($backup['size']) ? $this->formatFileSize($backup['size']) : 'Unknown';
                $type = str_contains($backup['name'], 'encrypted') ? '🔒' : '📄';
                $modified = isset($backup['server_modified']) ?
                    date('M j, H:i', strtotime($backup['server_modified'])) : '';

                $label = "{$type} {$backup['name']} ({$size}) {$modified}";
                $choices[$index] = $label;

                if ($index >= 9) break; // 最新10件まで表示
            }

            $choices['cancel'] = '❌ Cancel';

            $selected = $this->choice('Select backup to restore:', $choices);

            if ($selected === '❌ Cancel') {
                $this->info('Operation cancelled');
                return null;
            }

            // 選択されたインデックスを取得
            $selectedIndex = array_search($selected, $choices);
            $selectedBackup = $backups[$selectedIndex];

            return [
                'name' => $selectedBackup['name'],
                'path' => $selectedBackup['path_display'],
                'size' => $selectedBackup['size'] ?? 0
            ];

        } catch (Exception $e) {
            $this->error('❌ Failed to get backup list: ' . $e->getMessage());
            return null;
        }
    }

    private function decryptBackup(string $content): ?string
    {
        try {
            $package = json_decode($content, true);

            if (!$package) {
                throw new Exception('Invalid JSON format in backup file');
            }

            // データ整合性チェック
            if (!$this->encryptor->validateEncryptedData($package)) {
                throw new Exception('Corrupted or invalid encrypted backup data');
            }

            // バックアップ情報表示
            if (isset($package['metadata'])) {
                $metadata = $package['metadata'];
                $this->info('📋 Backup Information:');
                if (isset($metadata['created_at'])) {
                    $this->info("   📅 Created: " . date('Y-m-d H:i:s', strtotime($metadata['created_at'])));
                }
                if (isset($metadata['app_name'])) {
                    $this->info("   🏷️  App: {$metadata['app_name']}");
                }
                if (isset($metadata['environment'])) {
                    $this->info("   🌍 Environment: {$metadata['environment']}");
                }
                $this->line('');
            }

            // パスワード入力（3回まで）
            $attempts = 0;
            $maxAttempts = 3;

            while ($attempts < $maxAttempts) {
                $password = $this->secret('🔑 Enter decryption password:');
                $attempts++;

                try {
                    $decrypted = $this->encryptor->decrypt($package, $password);
                    $this->info('✅ Decryption successful');
                    return $decrypted;

                } catch (Exception $e) {
                    $remaining = $maxAttempts - $attempts;
                    if ($remaining > 0) {
                        $this->error("❌ Invalid password. {$remaining} attempts remaining");
                    }
                }
            }

            $this->error('❌ Maximum password attempts exceeded');
            return null;

        } catch (Exception $e) {
            $this->error('❌ Decryption failed: ' . $e->getMessage());
            return null;
        }
    }

    private function displayEnvironmentPreview(string $content, array $backupInfo): void
    {
        $this->info('🔍 Environment Preview:');
        $this->info("   📁 File: {$backupInfo['name']}");
        $this->info("   📊 Size: " . $this->formatFileSize(strlen($content)));
        $this->line('');

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

                $preview[] = [$key, substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '')];
            }
        }

        // 最初の10行を表示
        $displayCount = min(10, count($preview));
        $this->table(['Key', 'Value (truncated)'], array_slice($preview, 0, $displayCount));

        if (count($preview) > $displayCount) {
            $remaining = count($preview) - $displayCount;
            $this->info("... and {$remaining} more entries");
        }

        if ($secretCount > 0) {
            $this->warn("🔒 {$secretCount} sensitive values are masked in preview");
        }

        $this->line('');
    }

    private function backupCurrentEnv(): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            $this->warn('⚠️  No current .env file to backup');
            return;
        }

        $backupPath = base_path('.env.before_restore_' . now()->format('YmdHis'));
        copy($envPath, $backupPath);

        $this->info("💾 Current .env backed up to: " . basename($backupPath));
    }

    private function applyConfiguration(string $content): void
    {
        $envPath = base_path('.env');

        // 書き込み権限チェック
        if (file_exists($envPath) && !is_writable($envPath)) {
            throw new Exception('.env file is not writable');
        }

        // ディレクトリの書き込み権限チェック
        if (!is_writable(dirname($envPath))) {
            throw new Exception('Cannot write to application directory');
        }

        file_put_contents($envPath, $content);

        // ファイル権限設定
        chmod($envPath, 0600);

        // 設定キャッシュクリア
        $this->info('🧹 Clearing configuration cache...');
        $this->call('config:clear');
        $this->call('cache:clear');

        Log::info('Environment file restored via CLI', [
            'size' => strlen($content),
        ]);
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
}