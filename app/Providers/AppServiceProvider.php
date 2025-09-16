<?php

namespace App\Providers;

use App\Socialite\LineWorksProvider;
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
        // LINE WORKS Socialite provider registration
        Socialite::extend('lineworks', function ($app) {
            $config = $app['config']['services.lineworks'];

            return Socialite::buildProvider(LineWorksProvider::class, $config);
        });
    }
}
