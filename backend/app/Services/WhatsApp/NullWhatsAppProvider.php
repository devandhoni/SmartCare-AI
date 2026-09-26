<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;

class NullWhatsAppProvider implements WhatsAppProvider
{
    public function send(string $recipientNumber, string $message): ?string
    {
        /*
         * F7.3 deliberately performs no external network call.
         * A real provider will replace this implementation in a later step.
         */
        return null;
    }

    public function name(): string
    {
        return 'NULL';
    }
}