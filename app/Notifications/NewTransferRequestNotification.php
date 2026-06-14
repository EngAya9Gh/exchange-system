<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Transfer;
use App\Notifications\Channels\WhatsAppChannel;
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
        return ['database', WhatsAppChannel::class];
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
        $message .= "اسم المستفيد: {$this->transfer->recipient_name}\n";
        $message .= "📱 *رقم فودافون كاش*: {$this->transfer->recipient_phone}\n\n";
        $message .= "يمكنك نسخ الرقم أعلاه وتنفيذ التحويل مباشرة.";

        return [
            'to' => $notifiable->phone ?? '+963991239487',
            'message' => $message,
        ];
    }
}
