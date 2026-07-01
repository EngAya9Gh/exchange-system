<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Transfer;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewTransferRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Transfer $transfer
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database', WhatsAppChannel::class, TelegramChannel::class];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => 'طلب حوالة جديد',
            'message' => "طلب تحويل جديد بمبلغ {$this->transfer->amount} {$this->transfer->currency} من {$this->transfer->user->name}.",
            'request_id' => $this->transfer->id,
            'status' => 'pending',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', // Clock icon
        ];
    }

    public function toWhatsApp(mixed $notifiable): array
    {
        $message = "🔔 *طلب حوالة جديد (بانتظار المراجعة)*\n\n";
        $message .= "الزبون: {$this->transfer->user->name}\n";
        $message .= "المبلغ المطلوب: {$this->transfer->amount} {$this->transfer->currency}\n";
        $message .= "سعر الصرف: {$this->transfer->exchange_rate}\n";
        $message .= "المبلغ المستلم: {$this->transfer->received_amount} EGP\n";
        $message .= "اسم المستفيد: {$this->transfer->recipient_name}\n";
        $message .= "📱 *رقم فودافون كاش*: {$this->transfer->recipient_phone}\n\n";
        $message .= "يمكنك نسخ الرقم أعلاه وتنفيذ التحويل مباشرة.";

        return [
            'to' => $notifiable->phone ?? '+963991239487',
            'message' => $message,
        ];
    }

    public function toTelegram(mixed $notifiable): array
    {
        // Only send to the admin group once (check if this is the first admin to avoid duplicates)
        $firstAdmin = \App\Models\User::where('role', 'admin')->orderBy('id')->first();
        $isFirstAdmin = $firstAdmin && $notifiable->id === $firstAdmin->id;

        $to = env('TELEGRAM_ADMIN_GROUP_ID');
        
        // If no group ID is configured, send to the admin's private chat
        if (empty($to)) {
            $to = $notifiable->telegram_chat_id;
            $isFirstAdmin = true; // Send to their private chat
        }

        if (empty($to) || !$isFirstAdmin) {
            return [];
        }

        $message = "🔔 *طلب حوالة جديد (بانتظار المراجعة)*\n\n";
        $message .= "الزبون: {$this->transfer->user->name}\n";
        $message .= "المبلغ المطلوب: {$this->transfer->amount} {$this->transfer->currency}\n";
        $message .= "سعر الصرف: {$this->transfer->exchange_rate}\n";
        $message .= "المبلغ المستلم: {$this->transfer->received_amount} EGP\n";
        $message .= "اسم المستفيد: {$this->transfer->recipient_name}\n";
        $message .= "📱 *رقم المستفيد*: `{$this->transfer->recipient_phone}`\n";

        return [
            'to' => $to,
            'text' => $message,
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '🟢 قبول وإصدار إيصال', 'callback_data' => "approve_transfer_{$this->transfer->id}"]
                    ],
                    [
                        ['text' => '🔴 رفض الطلب', 'callback_data' => "reject_transfer_{$this->transfer->id}"]
                    ]
                ]
            ],
            'extra_messages' => [
                ['text' => "{$this->transfer->recipient_phone}"]
            ]
        ];
    }
}
