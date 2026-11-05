<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\SimOrder;
use App\Models\SimPackage;
use Illuminate\Support\Facades\Log;

/**
 * Posts new SIM package orders to the "Tele CARD" WhatsApp group through Wakeel —
 * the same provider/account the Vodafone Cash transfers group uses.
 * One message per order (not one per admin).
 */
class SimOrderGroupNotifier
{
    public function __construct(
        protected WhatsAppManager $whatsapp
    ) {}

    public function sendNewOrder(SimOrder $order): bool
    {
        $groupId = (string) config('whatsapp.providers.wakeel.sim_group_id');

        if ($groupId === '' || empty(config('whatsapp.providers.wakeel.api_key'))) {
            Log::warning('SIM WhatsApp group not configured; order ' . $order->order_number . ' was not posted.');
            return false;
        }

        return $this->whatsapp->provider('wakeel')->send($groupId, $this->newOrderMessage($order));
    }

    /** Always Arabic: the group is shared by the team. */
    public function newOrderMessage(SimOrder $order): string
    {
        $order->loadMissing(['user', 'package']);
        $t = fn (string $key, array $replace = []) => __("messages.{$key}", $replace, 'ar');

        $lines = [
            '📶 *' . $t('sim_new_order_title') . ($order->network_name ? ' - ' . $order->network_name : '') . '*',
            '',
            '👤 *' . ($order->user->name ?? '-') . '*',
            '```' . $order->phone_number . '```',
            '📦 ' . $order->package_name_ar,
        ];

        if ($order->package?->supplier_code) {
            $lines[] = '🔖 ' . $order->package->supplier_code;
        }

        $lines[] = '💰 *' . SimPackage::formatPrice($order->price) . ' ₺*';
        $lines[] = '🧾 ' . $order->order_number;

        if ($order->customer_name) {
            $lines[] = '🙋 ' . $t('sim_customer_name_short') . ': ' . $order->customer_name;
        }
        if ($order->notes) {
            $lines[] = '📝 ' . $t('sim_notes_short') . ': ' . $order->notes;
        }

        return implode("\n", $lines);
    }
}
