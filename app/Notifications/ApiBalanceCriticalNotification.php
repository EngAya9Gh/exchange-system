<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApiBalanceCriticalNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected float  $requiredAmount,
        protected float  $currentBalance,
        protected int    $kurumId,
        protected string $aboneNo
    ) {}

    public function via(mixed $notifiable): array
    {
        // database notification so it shows in the admin UI bell icon
        // + Telegram if admin has a chat ID or TELEGRAM_ADMIN_GROUP_ID is set
        $channels = ['database'];

        if (!empty(env('TELEGRAM_ADMIN_GROUP_ID')) || !empty($notifiable->telegram_chat_id)) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'title'           => '⚠️ رصيد API غير كافٍ',
            'message'         => "رصيد BayiWebPanel غير كافٍ لإتمام الدفع.\n"
                               . "المطلوب: {$this->requiredAmount} TL | المتاح: {$this->currentBalance} TL\n"
                               . "يجب شحن حساب BayiWebPanel فوراً.",
            'required_amount' => $this->requiredAmount,
            'current_balance' => $this->currentBalance,
            'kurum_id'        => $this->kurumId,
            'abone_no'        => $this->aboneNo,
            'type'            => 'api_balance_critical',
            'icon'            => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ];
    }

    public function toTelegram(mixed $notifiable): array
    {
        $chatId = env('TELEGRAM_ADMIN_GROUP_ID') ?: ($notifiable->telegram_chat_id ?? '');

        if (empty($chatId)) {
            return [];
        }

        $message = "🚨 *تنبيه عاجل - نظام دفع الفواتير*\n\n"
                 . "⚠️ *رصيد API BayiWebPanel غير كافٍ!*\n\n"
                 . "المبلغ المطلوب: *{$this->requiredAmount} TL*\n"
                 . "الرصيد الحالي: *{$this->currentBalance} TL*\n"
                 . "رقم المشترك: `{$this->aboneNo}`\n\n"
                 . "❗ يجب شحن حساب BayiWebPanel فوراً لاستمرار الخدمة.";

        return [
            'to'   => $chatId,
            'text' => $message,
        ];
    }
}
