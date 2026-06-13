<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $code) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return [WhatsAppChannel::class];
    }

    /**
     * Send message payload for WhatsApp.
     */
    public function toWhatsApp(mixed $notifiable): array
    {
        $message = "رمز التحقق الثنائي (2FA) الخاص بك هو: *{$this->code}*.\n"
                 . "هذا الرمز صالح لمدة 5 دقائق. يرجى عدم مشاركته مع أي شخص لدواعي الأمان.";
        
        return [
            'to' => $notifiable->phone,
            'message' => $message,
        ];
    }
}
