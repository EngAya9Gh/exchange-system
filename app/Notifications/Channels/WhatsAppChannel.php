<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(protected WhatsAppManager $whatsapp) {}

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $messageData = $notification->toWhatsApp($notifiable);
        
        $to = $messageData['to'] ?? $notifiable->routeNotificationFor('whatsapp') ?? $notifiable->phone;
        $message = $messageData['message'] ?? '';
        $media = $messageData['media'] ?? null;

        if (empty($to) || empty($message)) {
            return;
        }

        $this->whatsapp->send($to, $message, $media);
    }
}
