<?php

namespace App\Console\Commands;

use App\Services\DropboxService;
use App\Services\EnvEncryptionService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupEnvCommand extends Command
{
    protected $signature = 'backup:env
        {--password= : Encryption password (prompted if not provided)}
        {--no-encrypt : Skip encryption (NOT RECOMMENDED)}
        {--show-password : Display password in development (development only)}
        {--test : Test connection only}';

    protected $description = 'Backup .env file with strong encryption to Dropbox';

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
        $this->info('🔐 Environment File Backup Tool');
        $this->info('================================');

        // 接続テストのみ
        if ($this->option('test')) {
            return $this->testConnection();
        }

        // Dropbox認証チェック
        if (! $this->dropbox->isAuthenticated()) {
            $this->error('❌ Dropbox authentication required');
            $this->info('Please authenticate at: /admin/backup');

            return 1;
        }

        // .envファイル存在チェック
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            $this->error('❌ .env file not found');

            return 1;
        }

        // 暗号化オプション確認
        $useEncryption = ! $this->option('no-encrypt');

        if (! $useEncryption) {
            $this->warn('⚠️  WARNING: Saving .env without encryption');
            if (! $this->confirm('This is NOT RECOMMENDED. Continue?')) {
                $this->info('Operation cancelled');

                return 0;
            }
        }

        // パスワード取得
        $password = null;
        if ($useEncryption) {
            $password = $this->getPassword();
            if (! $password) {
                $this->error('❌ Valid password required for encryption');

                return 1;
            }
        }

        try {
            // .envファイル読み込み
            $plaintext = file_get_contents($envPath);
            $fileSize = strlen($plaintext);

            $this->info("📄 Reading .env file ({$fileSize} bytes)");

            // 暗号化またはそのまま保存
            if ($useEncryption) {
                $this->info('🔒 Encrypting with AES-256-CBC...');
                $package = $this->encryptor->encrypt($plaintext, $password);
                $content = json_encode($package, JSON_PRETTY_PRINT);
                $extension = '.json';
            } else {
                $content = $plaintext;
                $extension = '.txt';
            }

            // ファイル名生成
            $timestamp = now()->format('Y-m-d_H-i-s');
            $prefix = $useEncryption ? 'env_encrypted' : 'env_plain';
            $filename = "{$prefix}_{$timestamp}{$extension}";

            // 一時ファイル作成
            $tempDir = storage_path('app/temp');
            if (! File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $tempPath = "{$tempDir}/{$filename}";
            file_put_contents($tempPath, $content);

            // Dropboxにアップロード
            $this->info('☁️  Uploading to Dropbox...');
            $remotePath = "/env_secure/{$filename}";

            $uploadResult = $this->dropbox->uploadFile($tempPath, $remotePath);

            if ($uploadResult) {
                $this->info('✅ Environment backup completed successfully');
                $this->info("   📍 Location: {$remotePath}");
                $this->info('   📊 Size: '.number_format(strlen($content)).' bytes');

                if ($useEncryption) {
                    $this->warn('🔑 Remember your password! It cannot be recovered.');

                    if ($this->option('show-password') && app()->environment('local')) {
                        $this->info("🔓 Password (dev only): {$password}");
                    }
                }

                // 使用統計
                Log::info('Environment backup completed via CLI', [
                    'filename' => $filename,
                    'size' => strlen($content),
                    'encrypted' => $useEncryption,
                    'remote_path' => $remotePath,
                ]);

            } else {
                throw new Exception('Dropbox upload failed');
            }

            // 一時ファイル削除
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Backup failed: '.$e->getMessage());

            Log::error('Environment backup failed via CLI', [
                'error' => $e->getMessage(),
                'encrypted' => $useEncryption ?? false,
            ]);

            // 一時ファイルのクリーンアップ
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            return 1;

        } finally {
            // メモリからパスワードをクリア
            if (isset($password)) {
                unset($password);
            }
        }
    }

    private function getPassword(): ?string
    {
        $password = $this->option('password');

        if (! $password) {
            // 対話式パスワード入力
            $this->info('🔐 Password Requirements:');
            $this->info('   • Minimum 12 characters');
            $this->info('   • Must include: uppercase, lowercase, numbers, special chars');
            $this->line('');

            $password = $this->secret('Enter encryption password:');

            if (! $password) {
                return null;
            }

            $confirm = $this->secret('Confirm password:');

            if ($password !== $confirm) {
                $this->error('❌ Passwords do not match');

                return null;
            }
        }

        // パスワード強度チェック
        $errors = $this->encryptor->validatePasswordStrength($password);

        if (! empty($errors)) {
            $this->error('❌ Password does not meet security requirements:');
            foreach ($errors as $error) {
                $this->error("   • {$error}");
            }

            return null;
        }

        // 強度スコア表示
        $score = $this->encryptor->calculatePasswordScore($password);
        $strength = $this->getPasswordStrengthText($score);
        $this->info("🛡️  Password strength: {$strength} ({$score}/100)");

        if ($score < 70) {
            $this->warn('⚠️  Consider using a stronger password');
            if (! $this->confirm('Continue with this password?')) {
                return null;
            }
        }

        return $password;
    }

    private function getPasswordStrengthText(int $score): string
    {
        if ($score >= 90) {
            return 'Very Strong';
        }
        if ($score >= 80) {
            return 'Strong';
        }
        if ($score >= 70) {
            return 'Good';
        }
        if ($score >= 60) {
            return 'Fair';
        }
        if ($score >= 40) {
            return 'Weak';
        }

        return 'Very Weak';
    }

    private function testConnection(): int
    {
        $this->info('🔍 Testing Dropbox connection...');

        try {
            if (! $this->dropbox->isAuthenticated()) {
                $this->error('❌ Not authenticated with Dropbox');

                return 1;
            }

            $connectionTest = $this->dropbox->testConnection();
            $this->info('✅ Dropbox connection successful');

            // アカウント情報の表示（キーが存在する場合のみ）
            if (isset($connectionTest['name']['display_name'])) {
                $this->info("   👤 Account: {$connectionTest['name']['display_name']}");
            }
            if (isset($connectionTest['email'])) {
                $this->info("   📧 Email: {$connectionTest['email']}");
            }

            // /env_secureフォルダの存在確認
            try {
                $contents = $this->dropbox->listFolder('/env_secure');
                $backupCount = count($contents['entries'] ?? []);
                $this->info("   📁 Found {$backupCount} existing backups in /env_secure");
            } catch (Exception $e) {
                $this->info('   📁 /env_secure folder will be created on first backup');
            }

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Connection test failed: '.$e->getMessage());

            return 1;
        }
    }
}
