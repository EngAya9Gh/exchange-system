<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Channels\TelegramChannel;
use App\Models\Transfer;
use App\Services\ReceiptService;
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
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', WhatsAppChannel::class, TelegramChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $title = '';
        $message = '';
        $icon = '';

        if ($this->statusType === 'created') {
            $title = 'حوالة مالية جديدة';
            $message = "تم تسجيل حوالة جديدة برقم {$this->transfer->transfer_number}.";
            $icon = 'M12 6v6m0 0v6m0-6h6m-6 0H6'; // Plus icon
        } elseif ($this->statusType === 'paid') {
            $title = 'تم تسليم الحوالة';
            $message = "تم تسليم الحوالة رقم {$this->transfer->transfer_number} للمستفيد.";
            $icon = 'M5 13l4 4L19 7'; // Check icon
        } elseif ($this->statusType === 'cancelled') {
            $title = 'إلغاء حوالة';
            $message = "تم إلغاء الحوالة رقم {$this->transfer->transfer_number}.";
            $icon = 'M6 18L18 6M6 6l12 12'; // X icon
        }

        return [
            'title' => $title,
            'message' => $message,
            'transfer_id' => $this->transfer->id,
            'transfer_number' => $this->transfer->transfer_number,
            'status' => $this->statusType,
            'icon' => $icon,
        ];
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
            $message = "تم تسجيل الحوالة بنجاح\n"
                . "رقم الحوالة: *{$this->transfer->transfer_number}*\n"
                . "المبلغ المرسل: *{$this->transfer->source_amount} {$this->transfer->source_currency}*\n"
                . "المبلغ المستلم المتوقع: *{$this->transfer->received_amount} {$this->transfer->target_currency}*\n"
                . "الرمز السري للاستلام (مكون من 5 أرقام): *{$this->transfer->secret_code}*\n"
                . "الفرع المستهدف: *{$branchName}*\n"
                . "يرجى تقديم الرمز السري عند الاستلام.";
        } elseif ($this->statusType === 'paid') {
            $message = "تم تسليم الحوالة  بنجاح للمستفيد\n"
                . "رقم الحوالة: *{$this->transfer->transfer_number}*\n"
                . "المبلغ المدفوع: *{$this->transfer->received_amount} {$this->transfer->target_currency}*";
        } elseif ($this->statusType === 'cancelled') {
            $message = "تم إلغاء الحوالة  رقم *{$this->transfer->transfer_number}* بنجاح.";
        }

        return [
            'to' => $notifiable->phone,
            'message' => $message,
            'media' => $media,
        ];
    }

    public function toTelegram(mixed $notifiable): array
    {
        if (empty($notifiable->telegram_chat_id)) {
            return [];
        }

        $message = "";
        $document = null;
        $replyMarkup = null;
        $extraMessages = [];
        $branchName = $this->transfer->branch ? $this->transfer->branch->name : 'غير محدد';

        if ($this->statusType === 'created') {
            $message = "✅ *تم إنشاء الحوالة بنجاح!*\n\n"
                . "رقم الحوالة: `{$this->transfer->transfer_number}`\n"
                . "المبلغ: {$this->transfer->amount} {$this->transfer->currency}\n"
                . "المستفيد: {$this->transfer->recipient_name}\n";

            if ($this->transfer->secret_code) {
                $message .= "🔑 الرمز السري: `{$this->transfer->secret_code}`\n";
            }

            // Try generating the receipt document immediately
            try {
                $receiptService = app(ReceiptService::class);
                $pdfUrl = $receiptService->generatePdf($this->transfer);
                $document = url($pdfUrl);
            } catch (\Exception $e) {
                // Do nothing if receipt generation fails
            }

        } elseif ($this->statusType === 'paid') {
            $message = "💵 *تم تسليم الحوالة بنجاح!*\n\n"
                . "رقم الحوالة: `{$this->transfer->transfer_number}`\n"
                . "المبلغ المدفوع: {$this->transfer->received_amount} {$this->transfer->target_currency}";
                
            try {
                $receiptService = app(\App\Services\ReceiptService::class);
                $pdfUrl = $receiptService->generatePdf($this->transfer);
                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '📄 عرض الإيصال', 'url' => url($pdfUrl)]
                        ]
                    ]
                ];
            } catch (\Exception $e) {
                \Log::error("Failed to generate receipt link for paid transfer {$this->transfer->id}: " . $e->getMessage());
            }
            
        } elseif ($this->statusType === 'cancelled') {
            $message = "❌ *تم إلغاء الحوالة  *\n\n"
                . "رقم الحوالة: `{$this->transfer->transfer_number}`";
        }

        if (empty($message)) {
            return [];
        }

        if ($this->statusType === 'created') {
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => '🟢 قبول وإصدار إيصال', 'callback_data' => "approve_transfer_{$this->transfer->id}"]
                    ],
                    [
                        ['text' => '🔴 رفض الطلب', 'callback_data' => "reject_transfer_{$this->transfer->id}"]
                    ]
                ]
            ];

            if ($this->transfer->recipient_phone) {
                $extraMessages[] = ['text' => "{$this->transfer->recipient_phone}"];
            }
        } elseif ($document) {
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => '📄 عرض الإيصال', 'url' => $document]
                    ]
                ]
            ];
        }

        $payload = [
            'to' => $notifiable->telegram_chat_id,
            'text' => $message,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        if (!empty($extraMessages)) {
            $payload['extra_messages'] = $extraMessages;
        }

        return $payload;
    }
}
