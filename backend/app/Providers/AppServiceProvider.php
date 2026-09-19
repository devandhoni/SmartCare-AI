<?php

namespace App\Providers;

use App\Contracts\WhatsAppProvider;
use App\Services\ActivityLogCheckpointService;
use App\Services\WhatsApp\NullWhatsAppProvider;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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

        /*
         * F13: Checkpoint paths are installation-specific.
         * The signing key stays outside the Laravel project.
         * Registering this service does not create checkpoints.
         */
        $this->app->singleton(ActivityLogCheckpointService::class, function () {
            $directory = config('audit_checkpoint.directory');
            $keyPath = config('audit_checkpoint.key_path');

            if (! is_string($directory) || $directory === ''
                || ! is_string($keyPath) || $keyPath === '') {
                throw new RuntimeException(
                    'Audit checkpoint paths are not configured.'
                );
            }

            $signingKey = @file_get_contents($keyPath);

            if ($signingKey === false) {
                throw new RuntimeException(
                    'Audit checkpoint signing key could not be read.'
                );
            }

            return new ActivityLogCheckpointService(
                $directory,
                $signingKey
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}