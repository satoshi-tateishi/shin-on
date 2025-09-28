<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use ZipArchive;

class BackupService
{
    private DropboxService $dropboxService;

    public function __construct(DropboxService $dropboxService)
    {
        $this->dropboxService = $dropboxService;
    }

    public function createFullBackup(bool $uploadToDropbox = true): array
    {
        $timestamp = Carbon::now(config('backup.timezone', 'Asia/Tokyo'))->format('Y-m-d_H-i-s');
        $results = [];

        try {
            Log::info('Starting full backup process', ['timestamp' => $timestamp]);

            // データベースバックアップ
            if (config('backup.database.enabled', true)) {
                $dbBackupPath = $this->createDatabaseBackup($timestamp);
                $results['database'] = [
                    'success' => true,
                    'path' => $dbBackupPath,
                    'size' => File::size($dbBackupPath),
                ];
            }

            // ファイルバックアップ
            if (config('backup.files.enabled', true)) {
                $filesBackupPath = $this->createFilesBackup($timestamp);
                $results['files'] = [
                    'success' => true,
                    'path' => $filesBackupPath,
                    'size' => File::size($filesBackupPath),
                ];
            }

            // テストファイル
            if (config('backup.test.enabled', true)) {
                $testFilePath = $this->createTestFile($timestamp);
                $results['test'] = [
                    'success' => true,
                    'path' => $testFilePath,
                    'size' => File::size($testFilePath),
                ];
            }

            // Dropboxにアップロード
            if ($uploadToDropbox && $this->dropboxService->isAuthenticated()) {
                $this->uploadBackupsToDropbox($results, $timestamp);
                $results['dropbox_upload'] = ['success' => true];
            }

            Log::info('Full backup completed successfully', [
                'timestamp' => $timestamp,
                'results' => $results,
            ]);

            return [
                'success' => true,
                'timestamp' => $timestamp,
                'results' => $results,
            ];

        } catch (Exception $e) {
            Log::error('Backup process failed', [
                'timestamp' => $timestamp,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'timestamp' => $timestamp,
                'error' => $e->getMessage(),
                'results' => $results,
            ];
        }
    }

    public function testDropboxConnection(): array
    {
        try {
            if (! $this->dropboxService->isAuthenticated()) {
                return [
                    'success' => false,
                    'error' => 'Not authenticated with Dropbox',
                ];
            }

            $connectionTest = $this->dropboxService->testConnection();

            // テストファイルのアップロード・ダウンロードテスト
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $testContent = "Dropbox connection test from shin-on at {$timestamp}";
            $testFilePath = storage_path("app/temp/dropbox_test_{$timestamp}.txt");

            // テンポラリディレクトリ作成
            $tempDir = dirname($testFilePath);
            if (! File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            File::put($testFilePath, $testContent);

            $remoteTestPath = "/shin-on-backup/connection-test/test_{$timestamp}.txt";

            // アップロードテスト
            $this->dropboxService->uploadFile($testFilePath, $remoteTestPath);

            // ダウンロードテスト
            $downloadedContent = $this->dropboxService->downloadFile($remoteTestPath);

            // クリーンアップ
            File::delete($testFilePath);

            if ($downloadedContent === $testContent) {
                return [
                    'success' => true,
                    'message' => 'Dropbox connection test successful',
                    'account_info' => $connectionTest,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Upload/download content mismatch',
                ];
            }

        } catch (Exception $e) {
            Log::error('Dropbox connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function listBackups(): array
    {
        try {
            if (! $this->dropboxService->isAuthenticated()) {
                return [
                    'success' => false,
                    'error' => 'Not authenticated with Dropbox',
                ];
            }

            $backups = $this->dropboxService->findBackupFolders();

            return [
                'success' => true,
                'backups' => $backups,
            ];

        } catch (Exception $e) {
            Log::error('Failed to list backups', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createDatabaseBackup(string $timestamp): string
    {
        $filename = str_replace('{timestamp}', $timestamp, config('backup.database.filename_format', 'database_backup_{timestamp}.sql'));
        $backupPath = storage_path("app/backups/{$filename}");

        // バックアップディレクトリ作成
        $backupDir = dirname($backupPath);
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // データベース接続情報取得
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        if ($connection === 'mysql') {
            // MySQLダンプコマンド実行
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($backupPath)
            );

            $result = Process::run($command);

            if (! $result->successful()) {
                throw new Exception('Database backup failed: '.$result->errorOutput());
            }
        } else {
            // SQLiteや他のデータベースの場合はLaravelのクエリを使用
            $this->createDatabaseBackupFallback($backupPath);
        }

        if (! File::exists($backupPath) || File::size($backupPath) === 0) {
            throw new Exception('Database backup file was not created or is empty');
        }

        Log::info('Database backup created', [
            'path' => $backupPath,
            'size' => File::size($backupPath),
        ]);

        return $backupPath;
    }

    private function createDatabaseBackupFallback(string $backupPath): void
    {
        $sql = "-- shin-on Database Backup\n";
        $sql .= '-- Generated on: '.Carbon::now()."\n\n";

        // 全テーブルのデータをダンプ
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_'.config('database.connections.mysql.database');

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $sql .= "-- Table: {$tableName}\n";

            // テーブル構造取得
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
            $sql .= $createTable->{'Create Table'}.";\n\n";

            // データ取得
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($value) {
                    return $value === null ? 'NULL' : "'".addslashes($value)."'";
                }, (array) $row);

                $sql .= "INSERT INTO `{$tableName}` VALUES (".implode(', ', $values).");\n";
            }
            $sql .= "\n";
        }

        File::put($backupPath, $sql);
    }

    private function createFilesBackup(string $timestamp): string
    {
        $filename = str_replace('{timestamp}', $timestamp, config('backup.files.filename_format', 'files_backup_{timestamp}.zip'));
        $backupPath = storage_path("app/backups/{$filename}");

        $zip = new ZipArchive;
        if ($zip->open($backupPath, ZipArchive::CREATE) !== true) {
            throw new Exception("Cannot create zip file: {$backupPath}");
        }

        $paths = config('backup.files.paths', ['storage/app', 'public/uploads']);
        $excludePaths = config('backup.files.exclude_paths', []);

        foreach ($paths as $path) {
            $fullPath = base_path($path);
            if (File::exists($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $path, $excludePaths);
            }
        }

        $zip->close();

        if (! File::exists($backupPath) || File::size($backupPath) === 0) {
            throw new Exception('Files backup was not created or is empty');
        }

        Log::info('Files backup created', [
            'path' => $backupPath,
            'size' => File::size($backupPath),
        ]);

        return $backupPath;
    }

    private function createTestFile(string $timestamp): string
    {
        $filename = str_replace('{timestamp}', $timestamp, config('backup.test.filename_format', 'test_{timestamp}.txt'));
        $backupPath = storage_path("app/backups/{$filename}");

        $content = str_replace('{timestamp}', $timestamp, config('backup.test.content', 'Dropbox backup test from shin-on at {timestamp}'));

        File::put($backupPath, $content);

        return $backupPath;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $localPath, array $excludePaths): void
    {
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $localPath.'/'.$file->getRelativePathname();

            // 除外パスチェック
            $shouldExclude = false;
            foreach ($excludePaths as $excludePath) {
                if (fnmatch($excludePath, $relativePath)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if (! $shouldExclude) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function uploadBackupsToDropbox(array &$results, string $timestamp): void
    {
        foreach ($results as $type => &$result) {
            if ($result['success'] && isset($result['path'])) {
                try {
                    $filename = basename($result['path']);
                    $remotePath = $this->dropboxService->generateBackupPath($timestamp, $filename);

                    $this->dropboxService->uploadFile($result['path'], $remotePath);

                    $result['dropbox_path'] = $remotePath;
                    $result['dropbox_uploaded'] = true;

                    Log::info("Uploaded {$type} backup to Dropbox", [
                        'local_path' => $result['path'],
                        'remote_path' => $remotePath,
                    ]);

                    // Dropboxアップロード成功後、ローカルファイルを削除
                    if (File::exists($result['path'])) {
                        File::delete($result['path']);
                        Log::info("Deleted local backup file after successful upload", [
                            'local_path' => $result['path'],
                        ]);
                    }

                } catch (Exception $e) {
                    $result['dropbox_uploaded'] = false;
                    $result['dropbox_error'] = $e->getMessage();

                    Log::error("Failed to upload {$type} backup to Dropbox", [
                        'error' => $e->getMessage(),
                        'path' => $result['path'],
                    ]);
                }
            }
        }
    }
}
