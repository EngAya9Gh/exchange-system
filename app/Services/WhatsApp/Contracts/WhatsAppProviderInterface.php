<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Contracts;

interface WhatsAppProviderInterface
{
    /**
     * Send a WhatsApp message.
     *
     * @param string $to Phone number (with country code)
     * @param string $message Message content
     * @param array|null $media Optional media attachment
     * @return bool True if sent successfully, false otherwise
     */
    public function send(string $to, string $message, ?array $media = null): bool;
}
