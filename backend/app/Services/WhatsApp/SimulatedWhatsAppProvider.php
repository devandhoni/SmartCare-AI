<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;
use RuntimeException;

class SimulatedWhatsAppProvider implements WhatsAppProvider
{
    public function __construct(
        private readonly bool $shouldFail = false
    ) {
    }

    public function send(string $recipientNumber, string $message): ?string
    {
        if ($this->shouldFail) {
            throw new RuntimeException(
                'Simulated WhatsApp provider failure for F7.5 testing.'
            );
        }

        return 'simulated-' . bin2hex(random_bytes(8));
    }

    public function name(): string
    {
        return 'SIMULATED';
    }
}
