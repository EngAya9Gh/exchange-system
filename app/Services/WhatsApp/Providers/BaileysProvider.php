<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaileysProvider implements WhatsAppProviderInterface
{
    public function __construct(
        protected string $apiUrl,
        protected string $apiKey
    ) {}

    public function send(string $to, string $message, ?array $media = null): bool
    {
        if (empty($this->apiUrl)) {
            Log::error("WhatsApp Baileys: Missing API URL.");
            return false;
        }

        try {
            $payload = [
                'to' => $to,
                'message' => $message,
            ];

            if ($media) {
                $payload['media'] = $media;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->post(rtrim($this->apiUrl, '/') . '/send-message', $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error("WhatsApp Baileys failed to send message to {$to}.", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp Baileys sending exception: " . $e->getMessage());
            return false;
        }
    }
}
