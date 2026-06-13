<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UltraMsgProvider implements WhatsAppProviderInterface
{
    public function __construct(
        protected string $instanceId,
        protected string $token
    ) {}

    public function send(string $to, string $message, ?array $media = null): bool
    {
        if (empty($this->instanceId) || empty($this->token)) {
            Log::error("WhatsApp UltraMsg: Missing configuration instance_id or token.");
            return false;
        }

        try {
            // If media is provided, send as media (image/document)
            if ($media && isset($media['url'])) {
                $endpoint = "https://api.ultramsg.com/{$this->instanceId}/messages/image";
                $payload = [
                    'token' => $this->token,
                    'to' => $to,
                    'image' => $media['url'],
                    'caption' => $message
                ];
                
                if (isset($media['type']) && $media['type'] === 'document') {
                    $endpoint = "https://api.ultramsg.com/{$this->instanceId}/messages/document";
                    $payload = [
                        'token' => $this->token,
                        'to' => $to,
                        'document' => $media['url'],
                        'filename' => $media['filename'] ?? 'receipt.pdf',
                        'caption' => $message
                    ];
                }
            } else {
                $endpoint = "https://api.ultramsg.com/{$this->instanceId}/messages/chat";
                $payload = [
                    'token' => $this->token,
                    'to' => $to,
                    'body' => $message
                ];
            }

            $response = Http::post($endpoint, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error("WhatsApp UltraMsg failed to send message to {$to}.", [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp UltraMsg sending exception: " . $e->getMessage());
            return false;
        }
    }
}
