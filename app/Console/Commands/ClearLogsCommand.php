<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clear
                            {--days=7 : Number of days to keep logs}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old log files older than specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $force = $this->option('force');

        $logPath = storage_path('logs');
        $cutoffTime = now()->subDays($days);

        $this->info("🧹 Clearing log files older than {$days} days...");

        $logFiles = [
            'browser.log',
            'laravel.log',
        ];

        $totalSizeBefore = 0;
        $totalSizeAfter = 0;
        $clearedFiles = [];

        foreach ($logFiles as $logFile) {
            $filePath = $logPath . '/' . $logFile;

            if (File::exists($filePath)) {
                $fileSize = File::size($filePath);
                $totalSizeBefore += $fileSize;
                $fileModified = File::lastModified($filePath);

                if ($fileModified < $cutoffTime->timestamp) {
                    if (!$force && !$this->confirm("Delete {$logFile} (" . $this->formatBytes($fileSize) . ")?")) {
                        continue;
                    }

                    File::delete($filePath);
                    $clearedFiles[] = $logFile . ' (' . $this->formatBytes($fileSize) . ')';
                    $this->line("🗑️  Deleted: {$logFile}");
                } else {
                    $totalSizeAfter += $fileSize;
                    $this->line("⏭️  Kept: {$logFile} (modified recently)");
                }
            }
        }

        if (empty($clearedFiles)) {
            $this->info("✅ No log files need to be cleared.");
        } else {
            $this->newLine();
            $this->info("✅ Log cleanup completed!");
            $this->table(['Cleared Files'], array_map(fn($file) => [$file], $clearedFiles));

            $savedSpace = $totalSizeBefore - $totalSizeAfter;
            if ($savedSpace > 0) {
                $this->info("💾 Freed up: " . $this->formatBytes($savedSpace));
            }
        }

        return Command::SUCCESS;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
