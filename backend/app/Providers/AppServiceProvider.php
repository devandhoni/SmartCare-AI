<?php

namespace App\Providers;

use App\Contracts\WhatsAppProvider;
use App\Services\WhatsApp\NullWhatsAppProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * F7.3 safe default: no external WhatsApp traffic.
         * A real provider binding will replace this in a later F7 step.
         */
        $this->app->bind(
            WhatsAppProvider::class,
            NullWhatsAppProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
