<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\TelegramService;

class TelegramChannel
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        if (!$message || !isset($message['to'])) {
            return;
        }

        if (isset($message['document'])) {
            $this->telegramService->sendDocument(
                $message['to'],
                $message['document'],
                $message['text'] ?? ''
            );
        } else {
            $this->telegramService->sendMessage(
                $message['to'],
                $message['text'],
                $message['reply_markup'] ?? null
            );
        }

        if (isset($message['forward_to_whatsapp']) && $message['forward_to_whatsapp']) {
            // Use a dedicated group_text if available, otherwise fall back to Telegram text
            $groupText = $message['group_text'] ?? $message['text'];
            $cacheKey = "wa_forward_" . md5($groupText);
            if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                $this->telegramService->sendToWhatsAppGroup($groupText);
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, 60);
            }
        }

        // Send any extra messages (like a standalone phone number)
        if (isset($message['extra_messages']) && is_array($message['extra_messages'])) {
            foreach ($message['extra_messages'] as $extraMessage) {
                $this->telegramService->sendMessage(
                    $message['to'],
                    $extraMessage['text'] ?? '',
                    $extraMessage['reply_markup'] ?? null
                );
            }
        }
    }
}
