<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\SimOrder;
use App\Models\SimPackage;
use Illuminate\Support\Facades\Log;

/**
 * Posts new SIM package orders to the "Tele CARD" WhatsApp group through Wakeel —
 * the same provider/account the Vodafone Cash transfers group uses.
 * One short message per order (not one per admin), always in Arabic.
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

    /**
     * 📶 *طلب باقة جديد - Tele CARD*
     *
     * 👤 *اسم المحل*
     * ```51234567```            ← last 8 digits only, on its own line to copy
     * 💰 قيمة الرصيد: *350 ₺*
     * 📦 تركيا 10 جيجا - 30 يوم
     */
    public function newOrderMessage(SimOrder $order): string
    {
        $order->loadMissing(['user', 'package']);
        $t = fn (string $key) => __("messages.{$key}", [], 'ar');

        $lines = [
            '📶 *' . $t('sim_new_order_title') . ($order->network_name ? ' - ' . $order->network_name : '') . '*',
            '',
            '👤 *' . ($order->user->name ?? '-') . '*',
            '```' . $order->supplierPanelNumber() . '```',
            '💰 ' . $t('sim_balance_value') . ': *' . SimPackage::formatPrice($order->price) . ' ₺*',
            '📦 ' . $this->shortPackageName($order),
        ];

        if ($order->customer_name) {
            $lines[] = '🙋 ' . $t('sim_customer_name_short') . ': ' . $order->customer_name;
        }
        if ($order->notes) {
            $lines[] = '📝 ' . $t('sim_notes_short') . ': ' . $order->notes;
        }

        return implode("\n", $lines);
    }

    /** "تركيا 10 جيجا - 30 يوم", "تركيا 50 جيجا - 6 أشهر", "أوروبا 5 جيجا - 30 يوم" */
    protected function shortPackageName(SimOrder $order): string
    {
        $package = $order->package;
        if (!$package) {
            return $order->package_name_ar;
        }

        return sprintf(
            '%s %d جيجا - %s',
            $order->region === SimPackage::REGION_EUROPE ? 'أوروبا' : 'تركيا',
            $package->data_gb,
            $this->arabicDuration($package->validity_days)
        );
    }

    protected function arabicDuration(int $days): string
    {
        if ($days > 30 && $days % 30 === 0) {
            $months = intdiv($days, 30);

            return match (true) {
                $months === 2 => 'شهرين',
                $months <= 10 => $months . ' أشهر',
                default => $months . ' شهراً',
            };
        }

        return $days . ' يوم';
    }
}
