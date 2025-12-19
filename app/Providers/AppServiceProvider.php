<?php

namespace App\Providers;

use App\Http\ViewComposers\EquipmentViewComposer;
use App\Http\ViewComposers\PerformanceViewComposer;
use App\Socialite\LineWorksProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 本番環境でHTTPSを強制（リバースプロキシ対応）
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // LINE WORKS Socialite provider registration
        Socialite::extend('lineworks', function ($app) {
            $config = $app['config']['services.lineworks'];

            return Socialite::buildProvider(LineWorksProvider::class, $config);
        });

        // View Composers
        View::composer([
            'performances.*',
            'phases.*',
        ], PerformanceViewComposer::class);

        View::composer([
            'phase-equipment.*',
            'equipment-transfer.*',
        ], EquipmentViewComposer::class);
    }
}
