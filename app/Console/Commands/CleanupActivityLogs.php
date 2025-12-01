<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class CleanupActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:cleanup
                            {--months=6 : 削除対象の経過月数}
                            {--dry-run : 実際には削除せず件数のみ表示}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '指定月数経過したアクティビティログを削除します';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = (int) $this->option('months');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subMonths($months);

        $query = ActivityLog::where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->info("削除対象のアクティビティログはありません。");

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[ドライラン] {$count}件のアクティビティログが削除対象です。");
            $this->info("（{$cutoffDate->format('Y-m-d')} より前のログ）");

            return Command::SUCCESS;
        }

        // 確認プロンプト（インタラクティブモードの場合）
        if ($this->input->isInteractive()) {
            if (! $this->confirm("{$count}件のアクティビティログを削除しますか？")) {
                $this->info('キャンセルしました。');

                return Command::SUCCESS;
            }
        }

        // 削除実行
        $deleted = $query->delete();

        $this->info("{$deleted}件のアクティビティログを削除しました。");
        $this->info("（{$cutoffDate->format('Y-m-d')} より前のログ）");

        return Command::SUCCESS;
    }
}
