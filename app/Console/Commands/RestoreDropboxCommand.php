<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Exception;
use Illuminate\Console\Command;

class RestoreDropboxCommand extends Command
{
    protected $signature = 'restore:dropbox
                            {--list : List available backups}
                            {backup? : Backup folder name to restore (YYYY-MM-DD_HH-mm-ss format)}';

    protected $description = 'Restore backup from Dropbox';

    public function handle(BackupService $backupService): int
    {
        try {
            // バックアップ一覧表示のみの場合
            if ($this->option('list')) {
                return $this->listBackups($backupService);
            }

            $backupName = $this->argument('backup');

            if (! $backupName) {
                $this->error('❌ Please specify a backup to restore or use --list to see available backups');
                $this->info('💡 Example: restore:dropbox 2025-09-29_06-30-00');

                return Command::FAILURE;
            }

            $this->warn('🚨 WARNING: This will restore the backup and may overwrite current data!');

            if (! $this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }

            $this->info("🔄 Starting restore process for backup: {$backupName}");

            // Step 1: Dropboxからバックアップをダウンロード
            $this->info('📥 Downloading backup from Dropbox...');
            $downloadResult = $backupService->downloadBackupFromDropbox($backupName);

            if (! $downloadResult['success']) {
                $this->error("❌ Failed to download backup: {$downloadResult['error']}");

                return Command::FAILURE;
            }

            $this->info('✅ Backup downloaded successfully');
            $this->info("📁 Download directory: {$downloadResult['download_dir']}");

            // ダウンロードしたファイルを表示
            $this->newLine();
            $this->info('📋 Downloaded files:');
            foreach ($downloadResult['files'] as $file) {
                $sizeKb = round($file['size'] / 1024, 2);
                $this->line("   - {$file['filename']} ({$sizeKb} KB) [{$file['type']}]");
            }

            // Step 2: データベース復元
            $sqlFile = null;
            foreach ($downloadResult['files'] as $file) {
                if ($file['type'] === 'database') {
                    $sqlFile = $file['local_path'];
                    break;
                }
            }

            if ($sqlFile) {
                $this->newLine();
                $this->info('🗄️ Restoring database...');

                if (! $this->confirm('Do you want to restore the database? (A backup will be created first)')) {
                    $this->info('⏭️ Skipping database restore');
                } else {
                    $restoreResult = $backupService->restoreDatabase($sqlFile, true);

                    if (! $restoreResult['success']) {
                        $this->error("❌ Database restore failed: {$restoreResult['error']}");

                        return Command::FAILURE;
                    }

                    $this->info('✅ Database restored successfully');
                }
            } else {
                $this->warn('⚠️ No database backup file found in this backup');
            }

            // Step 3: ファイル復元
            $filesZip = null;
            foreach ($downloadResult['files'] as $file) {
                if ($file['type'] === 'files') {
                    $filesZip = $file['local_path'];
                    break;
                }
            }

            if ($filesZip) {
                $this->newLine();
                $this->info('📂 Restoring files...');

                if (! $this->confirm('Do you want to restore files? (This will overwrite existing files)')) {
                    $this->info('⏭️ Skipping files restore');
                } else {
                    $filesRestoreResult = $backupService->restoreFiles($filesZip);

                    if (! $filesRestoreResult['success']) {
                        $this->error("❌ Files restore failed: {$filesRestoreResult['error']}");

                        return Command::FAILURE;
                    }

                    $this->info('✅ Files restored successfully');
                }
            } else {
                $this->warn('⚠️ No files backup found in this backup');
            }

            // Step 4: クリーンアップ
            $this->newLine();
            if ($this->confirm('Do you want to clean up temporary restore files?', true)) {
                $backupService->cleanupRestoreFiles($backupName);
                $this->info('🧹 Temporary files cleaned up');
            }

            $this->newLine();
            $this->info('🎉 Restore process completed successfully!');

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Restore process failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    private function listBackups(BackupService $backupService): int
    {
        $this->info('📋 Listing available backups from Dropbox...');

        $result = $backupService->listBackups();

        if (! $result['success']) {
            $this->error("❌ Failed to list backups: {$result['error']}");

            return Command::FAILURE;
        }

        $backups = $result['backups'];

        if (empty($backups)) {
            $this->info('📭 No backups found in Dropbox');

            return Command::SUCCESS;
        }

        $this->info('✅ Found '.count($backups).' backup(s):');
        $this->newLine();

        $headers = ['Backup Name', 'Path'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $backup['name'],
                $backup['full_path'],
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('💡 To restore a backup, use: restore:dropbox <backup-name>');
        $this->info('📝 Example: restore:dropbox 2025-09-29_06-30-00');

        return Command::SUCCESS;
    }
}
