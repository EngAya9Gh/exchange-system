<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SimOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app (bell) notification for users with the `manage_sims` permission.
 * The WhatsApp alert goes once to the "Tele CARD" group (see SimOrderGroupNotifier), not per admin.
 */
class NewSimOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected SimOrder $order
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
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
}
