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
        // セキュリティヘッダーミドルウェアをグローバルに適用
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // API レート制限を強化
        $middleware->throttleRequests('api')->with(60, 1);

        // Webルートの一般的な保護を強化
        $middleware->throttleRequests('web')->with(1000, 1);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
