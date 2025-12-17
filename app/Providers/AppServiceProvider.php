<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- 1. TAMBAHKAN BARIS INI DI ATAS

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
        // --- 2. TAMBAHKAN KODE INI DI DALAM BOOT ---
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}