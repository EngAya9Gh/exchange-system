<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhapiProvider implements WhatsAppProviderInterface
{
    public function __construct(
        protected string $apiUrl,
        protected string $token
    ) {}

    public function send(string $to, string $message, ?array $media = null): bool
    {
        if (empty($this->token)) {
            Log::error("WhatsApp Whapi: Missing API token.");
            return false;
        }

        $baseUrl = rtrim($this->apiUrl, '/');

        try {
            if ($media && isset($media['url'])) {
                $endpoint = "{$baseUrl}/messages/image";
                $payload = [
                    'to' => $to,
                    'media' => $media['url'],
                    'caption' => $message
                ];

                if (isset($media['type']) && $media['type'] === 'document') {
                    $endpoint = "{$baseUrl}/messages/document";
                    $payload = [
                        'to' => $to,
                        'media' => $media['url'],
                        'filename' => $media['filename'] ?? 'receipt.pdf',
                        'caption' => $message
                    ];
                }
            } else {
                $endpoint = "{$baseUrl}/messages/text";
                $payload = [
                    'to' => $to,
                    'body' => $message
                ];
            }

            $response = Http::withToken($this->token)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error("WhatsApp Whapi failed to send message to {$to}.", [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp Whapi sending exception: " . $e->getMessage());
            return false;
        }
    }
}
