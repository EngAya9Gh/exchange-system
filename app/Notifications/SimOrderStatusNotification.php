<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Concerns\EscapesTelegramMarkdown;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Sent to the order owner when an admin activates or rejects their SIM package order. */
class SimOrderStatusNotification extends Notification
{
    use Queueable, EscapesTelegramMarkdown;

    public function __construct(
        protected SimOrder $order,
        protected string $status
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database', TelegramChannel::class, WhatsAppChannel::class];
    }

    protected function replacements(): array
    {
        return [
            'number' => $this->order->order_number,
            'package' => $this->order->localized_package_name,
            'phone' => $this->order->phone_number,
            'amount' => SimPackage::formatPrice($this->order->charged_amount),
            'reason' => $this->order->admin_notes ?? '-',
        ];
    }

    protected function isCompleted(): bool
    {
        return $this->status === SimOrder::STATUS_COMPLETED;
    }

    public function toArray(mixed $notifiable): array
    {
        $key = $this->isCompleted() ? 'sim_order_completed' : 'sim_order_rejected';

        return [
            'title' => __("messages.{$key}_title"),
            'message' => __("messages.{$key}_desc", $this->replacements()),
            'sim_order_id' => $this->order->id,
            'status' => $this->status,
            'icon' => $this->isCompleted() ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12',
        ];
    }

    public function toWhatsApp(mixed $notifiable): array
    {
        $key = $this->isCompleted() ? 'sim_order_completed_whatsapp' : 'sim_order_rejected_whatsapp';

        return [
            'to' => $notifiable->phone,
            'message' => __("messages.{$key}", $this->replacements()),
            'media' => null,
        ];
    }

    public function toTelegram(mixed $notifiable): array
    {
        if (empty($notifiable->telegram_chat_id)) {
            return [];
        }

        $key = $this->isCompleted() ? 'sim_order_completed_whatsapp' : 'sim_order_rejected_whatsapp';
        $replacements = array_map(fn ($value) => $this->tg((string) $value), $this->replacements());

        return [
            'to' => $notifiable->telegram_chat_id,
            'text' => __("messages.{$key}", $replacements),
        ];
    }
}
