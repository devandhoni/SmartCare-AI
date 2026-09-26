<?php

namespace App\Providers;

use App\Contracts\WhatsAppProvider;
use App\Services\ActivityLogCheckpointService;
use App\Services\WhatsApp\LocalWhatsAppProvider;
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
         * WhatsApp provider selection.
         *
         * Safe default:
         *   WHATSAPP_PROVIDER=null
         *
         * Temporary local delivery testing:
         *   WHATSAPP_PROVIDER=local
         */
        $this->app->bind(WhatsAppProvider::class, function () {
            $provider = strtolower(
                trim((string) config('services.whatsapp.provider', ''))
            );

            if ($provider === '') {
                $provider = 'null';
            }

            return match ($provider) {
                'local' => app(LocalWhatsAppProvider::class),
                'null' => app(NullWhatsAppProvider::class),

                default => throw new RuntimeException(
                    "Unsupported WhatsApp provider [{$provider}]."
                ),
            };
        });

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