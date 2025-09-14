<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use App\Socialite\LineWorksProvider;

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
        // LINE WORKS Socialite provider registration
        Socialite::extend('lineworks', function ($app) {
            $config = $app['config']['services.lineworks'];
            return Socialite::buildProvider(LineWorksProvider::class, $config);
        });
    }
}
