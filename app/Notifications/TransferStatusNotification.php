<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransferStatusNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Transfer $transfer,
        protected string $statusType
    ) {}

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
        $message = "";
        $media = null;

        // Fetch relationship if not loaded
        $branchName = $this->transfer->branch ? $this->transfer->branch->name : 'غير محدد';

        if ($this->statusType === 'created') {
            $message = "تم تسجيل الحوالة المالية بنجاح!\n"
                     . "رقم الحوالة: *{$this->transfer->transfer_number}*\n"
                     . "المبلغ المرسل: *{$this->transfer->source_amount} {$this->transfer->source_currency}*\n"
                     . "المبلغ المستلم المتوقع: *{$this->transfer->received_amount} {$this->transfer->target_currency}*\n"
                     . "الرمز السري للاستلام (مكون من 5 أرقام): *{$this->transfer->secret_code}*\n"
                     . "الفرع المستهدف: *{$branchName}*\n"
                     . "يرجى تقديم الرمز السري عند الاستلام.";
        } elseif ($this->statusType === 'paid') {
            $message = "تم تسليم الحوالة المالية بنجاح للمستفيد!\n"
                     . "رقم الحوالة: *{$this->transfer->transfer_number}*\n"
                     . "المبلغ المدفوع: *{$this->transfer->received_amount} {$this->transfer->target_currency}*";
        } elseif ($this->statusType === 'cancelled') {
            $message = "تم إلغاء الحوالة المالية رقم *{$this->transfer->transfer_number}* بنجاح.";
        }

        return [
            'to' => $notifiable->phone,
            'message' => $message,
            'media' => $media,
        ];
    }
}
