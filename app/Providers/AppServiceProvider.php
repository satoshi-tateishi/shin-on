<?php

namespace App\Providers;

use App\Http\ViewComposers\EquipmentViewComposer;
use App\Http\ViewComposers\PerformanceViewComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
