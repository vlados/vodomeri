<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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
         \Carbon\Carbon::setLocale('bg_BG');

        // Set default date format for Bulgaria
//         \Carbon\Carbon::setToStringFormat('d.m.Y H:i');

        // Register the Symfony Mailer Postmark transport
    }
}
