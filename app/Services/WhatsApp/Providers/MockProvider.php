<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use Illuminate\Support\Facades\Log;

class MockProvider implements WhatsAppProviderInterface
{
    public function send(string $to, string $message, ?array $media = null): bool
    {
        Log::info("WhatsApp Mock Message Sent!", [
            'to' => $to,
            'message' => $message,
            'media' => $media,
        ]);
        
        return true;
    }
}
