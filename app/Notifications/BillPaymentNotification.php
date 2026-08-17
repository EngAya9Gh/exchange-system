<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Channels\TelegramChannel;
use App\Models\BillPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BillPaymentNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected BillPayment $billPayment,
        protected string $statusType
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['database', WhatsAppChannel::class, TelegramChannel::class];
    }

    public function toArray(mixed $notifiable): array
    {
        $title = '';
        $message = '';
        $icon = '';

        if ($this->statusType === 'completed') {
            $title = __('messages.bill_paid_title');
            $message = __('messages.bill_paid_desc', ['abone_no' => $this->billPayment->abone_no]);
            $icon = 'M5 13l4 4L19 7'; // Check icon
        } elseif ($this->statusType === 'failed') {
            $title = __('messages.bill_failed_title');
            $message = __('messages.bill_failed_desc', ['abone_no' => $this->billPayment->abone_no]);
            $icon = 'M6 18L18 6M6 6l12 12'; // X icon
        }

        return [
            'title' => $title,
            'message' => $message,
            'bill_id' => $this->billPayment->id,
            'abone_no' => $this->billPayment->abone_no,
            'status' => $this->statusType,
            'icon' => $icon,
        ];
    }

    public function toWhatsApp(mixed $notifiable): array
    {
        $message = "";

        if ($this->statusType === 'completed') {
            $message = __('messages.bill_paid_whatsapp', [
                'abone_no' => $this->billPayment->abone_no,
                'amount' => $this->billPayment->amount
            ]);
        } elseif ($this->statusType === 'failed') {
            $message = __('messages.bill_failed_whatsapp', [
                'abone_no' => $this->billPayment->abone_no
            ]);
        }

        if (empty($message)) {
            return [];
        }

        return [
            'to' => $notifiable->phone,
            'message' => $message,
            'media' => null,
        ];
    }

    public function toTelegram(mixed $notifiable): array
    {
        if (empty($notifiable->telegram_chat_id)) {
            return [];
        }

        $message = "";

        if ($this->statusType === 'completed') {
            $message = __('messages.bill_paid_telegram', [
                'abone_no' => $this->billPayment->abone_no,
                'amount' => $this->billPayment->amount
            ]);
        } elseif ($this->statusType === 'failed') {
            $message = __('messages.bill_failed_telegram', [
                'abone_no' => $this->billPayment->abone_no
            ]);
        }

        if (empty($message)) {
            return [];
        }

        return [
            'to' => $notifiable->telegram_chat_id,
            'text' => $message,
        ];
    }
}
