<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\Providers\MockProvider;
use App\Services\WhatsApp\Providers\UltraMsgProvider;
use App\Services\WhatsApp\Providers\WhapiProvider;
use App\Services\WhatsApp\Providers\BaileysProvider;
use InvalidArgumentException;

class WhatsAppManager
{
    protected array $providers = [];

    /**
     * Resolve a provider instance.
     *
     * @param string|null $name
     * @return WhatsAppProviderInterface
     */
    public function provider(?string $name = null): WhatsAppProviderInterface
    {
        $name = $name ?: config('whatsapp.default', 'mock');

        if (!isset($this->providers[$name])) {
            $this->providers[$name] = $this->createProvider($name);
        }

        return $this->providers[$name];
    }

    /**
     * Create the provider instance.
     *
     * @param string $name
     * @return WhatsAppProviderInterface
     */
    protected function createProvider(string $name): WhatsAppProviderInterface
    {
        $config = config("whatsapp.providers.{$name}", []);

        switch ($name) {
            case 'mock':
                return new MockProvider();
            case 'ultramsg':
                return new UltraMsgProvider(
                    (string) ($config['instance_id'] ?? ''),
                    (string) ($config['token'] ?? '')
                );
            case 'whapi':
                return new WhapiProvider(
                    (string) ($config['api_url'] ?? ''),
                    (string) ($config['token'] ?? '')
                );
            case 'baileys':
                return new BaileysProvider(
                    (string) ($config['api_url'] ?? ''),
                    (string) ($config['api_key'] ?? '')
                );
            case 'wakeel':
                return new class(
                    (string) ($config['api_key'] ?? '')
                ) implements \App\Services\WhatsApp\Contracts\WhatsAppProviderInterface {
                    public function __construct(protected string $apiKey) {}
                    
                    public function send(string $to, string $message, ?array $media = null): bool
                    {
                        try {
                            // Clean phone for Wakeel API (keep @g.us for groups, strip + or 00 for personal)
                            $cleanTo = $to;
                            if (!str_contains($cleanTo, '@g.us')) {
                                $cleanTo = preg_replace('/^00/', '', $cleanTo);
                                $cleanTo = preg_replace('/[^0-9]/', '', $cleanTo);
                            }

                            $response = \Illuminate\Support\Facades\Http::timeout(30)
                                ->withToken($this->apiKey)
                                ->post('https://provider.wakeel.cc/api/v1/message/send', [
                                    'phone' => $cleanTo,
                                    'message' => $message
                                ]);
                            if ($response->successful()) {
                                \Illuminate\Support\Facades\Log::info("WhatsApp Wakeel Message Sent Successfully to {$to}");
                                return true;
                            }
                            \Illuminate\Support\Facades\Log::error("WhatsApp Wakeel API Error: " . $response->body());
                            return false;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('WhatsApp Wakeel API Exception: ' . $e->getMessage());
                            return false;
                        }
                    }
                };
            default:
                throw new InvalidArgumentException("WhatsApp provider [{$name}] is not supported.");
        }
    }

    /**
     * Send a message using the default provider.
     *
     * @param string $to
     * @param string $message
     * @param array|null $media
     * @return bool
     */
    public function send(string $to, string $message, ?array $media = null): bool
    {
        return $this->provider()->send($to, $message, $media);
    }
}
