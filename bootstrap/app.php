<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // リバースプロキシの信頼設定（HTTPSの正しい検出に必要）
        $middleware->prepend(\App\Http\Middleware\TrustProxies::class);

        // セキュリティヘッダーミドルウェアをグローバルに適用
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // portal_jwt クッキーは Portal が署名済みのため暗号化しない
        $middleware->encryptCookies(except: [
            'portal_jwt',
        ]);

        // カスタムミドルウェアのエイリアス登録
        $middleware->alias([
            'portal.auth' => \App\Http\Middleware\PortalJwtAuthenticate::class,
            'performance.access' => \App\Http\Middleware\CheckPerformanceAccess::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        // 毎日午前6時に機材ステータスを自動更新
        $schedule->command('phase:update-equipment-status')->dailyAt('06:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
