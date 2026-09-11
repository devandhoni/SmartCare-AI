<?php

namespace App\Contracts;

interface WhatsAppProvider
{
    /**
     * Send a WhatsApp message.
     *
     * Providers should return a provider message identifier when available.
     * Throw an exception when delivery submission fails.
     */
    public function send(string $recipientNumber, string $message): ?string;

    /**
     * Human-readable provider name stored in the delivery log.
     */
    public function name(): string;
}
