<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 自動ログクリーンアップ（毎日深夜2時に実行）
Schedule::command('logs:clear --days=7 --force')->dailyAt('02:00');

// アクティビティログクリーンアップ（毎週日曜深夜3時に実行、6ヶ月経過分を削除）
Schedule::command('activity-logs:cleanup --months=6')->weeklyOn(0, '03:00');
