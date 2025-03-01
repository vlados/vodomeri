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
        // Set Carbon locale to Bulgarian
        // \Carbon\Carbon::setLocale('bg_BG');
        
        // Set default date format for Bulgaria
        // \Carbon\Carbon::setToStringFormat('d.m.Y H:i');
    }
}
