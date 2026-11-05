<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Concerns\EscapesTelegramMarkdown;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Sent to users with the `manage_sims` permission when a SIM package is ordered. */
class NewSimOrderNotification extends Notification
{
    use Queueable, EscapesTelegramMarkdown;

    public function __construct(
        protected SimOrder $order
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database', TelegramChannel::class];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => __('messages.sim_new_order_title'),
            'message' => __('messages.sim_new_order_desc', [
                'user' => $this->order->user->name ?? '',
                'package' => $this->order->localized_package_name,
                'phone' => $this->order->phone_number,
            ]),
            'sim_order_id' => $this->order->id,
            'status' => SimOrder::STATUS_PENDING,
            'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', // phone icon
        ];
    }

    public function toTelegram(mixed $notifiable): array
    {
        if (empty($notifiable->telegram_chat_id)) {
            return [];
        }

        $order = $this->order;
        $type = $order->isEsim() ? __('messages.sim_type_esim') : __('messages.sim_type_physical');

        $text = '🛒 *' . __('messages.sim_new_order_title') . "*\n\n";
        $text .= __('messages.sim_order_number') . ": `{$order->order_number}`\n";
        $text .= __('messages.sim_ordered_by') . ': ' . $this->tg($order->user->name ?? '') . "\n";
        $text .= __('messages.sim_package') . ': ' . $this->tg($order->localized_package_name) . "\n";
        $text .= __('messages.sim_type') . ': ' . $this->tg($type) . "\n";
        $text .= '📱 ' . __('messages.sim_phone') . ": `{$order->phone_number}`\n";
        if ($order->customer_name) {
            $text .= __('messages.sim_customer_name_short') . ': ' . $this->tg($order->customer_name) . "\n";
        }
        if ($order->notes) {
            $text .= __('messages.sim_notes_short') . ': ' . $this->tg($order->notes) . "\n";
        }
        $text .= '💰 ' . __('messages.sim_price') . ': ' . SimPackage::formatPrice($order->price) . ' ₺';

        return [
            'to' => $notifiable->telegram_chat_id,
            'text' => $text,
        ];
    }
}
