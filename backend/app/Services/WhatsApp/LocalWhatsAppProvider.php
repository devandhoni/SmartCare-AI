<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LocalWhatsAppProvider implements WhatsAppProvider
{
    public function send(string $recipientNumber, string $message): ?string
    {
        $url = rtrim(
            (string) config('services.whatsapp.local_url'),
            '/'
        );

        if ($url === '') {
            throw new RuntimeException(
                'Local WhatsApp gateway URL is not configured.'
            );
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->asJson()
            ->post($url . '/send', [
                'number' => $recipientNumber,
                'message' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Local WhatsApp gateway rejected the message: '
                . $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data) || ($data['success'] ?? false) !== true) {
            throw new RuntimeException(
                'Local WhatsApp gateway did not confirm the message submission.'
            );
        }

        $messageId = $data['messageId'] ?? null;

        return is_string($messageId) && $messageId !== ''
            ? $messageId
            : null;
    }

    public function name(): string
    {
        return 'LOCAL_WHATSAPP';
    }
}