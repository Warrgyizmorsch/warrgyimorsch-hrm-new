<?php

namespace App\Providers;

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
        // App\Listeners\RecordUserLogin / RecordUserLogout are auto-discovered from their
        // typed handle() signatures (Laravel 11+ event auto-discovery) — do not also
        // register them here, or Login/Logout listeners fire twice per event.
    }
}
