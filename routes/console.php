<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 自動ログクリーンアップ（毎日深夜2時に実行）
Schedule::command('logs:clear --days=7 --force')->dailyAt('02:00');
