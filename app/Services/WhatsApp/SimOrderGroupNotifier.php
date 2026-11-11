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
        return $this->post($this->newOrderMessage($order), $order);
    }

    /** Follow-up once kontor86 settles an automatic load, so the group always sees how it ended. */
    public function sendStatusUpdate(SimOrder $order): bool
    {
        $message = $this->statusUpdateMessage($order);

        return $message !== null && $this->post($message, $order);
    }

    protected function post(string $message, SimOrder $order): bool
    {
        $groupId = (string) config('whatsapp.providers.wakeel.sim_group_id');

        if ($groupId === '' || empty(config('whatsapp.providers.wakeel.api_key'))) {
            Log::warning('SIM WhatsApp group not configured; order ' . $order->order_number . ' was not posted.');
            return false;
        }

        return $this->whatsapp->provider('wakeel')->send($groupId, $message);
    }

    /**
     * ✅ *تم تحميل الباقة تلقائياً*        or   ❌ *ألغى المورد التحميل وتم إرجاع المبلغ للزبون*
     *
     * 👤 *اسم المحل*
     * ```5051234567```
     * 📦 تركيا 10 جيجا - 30 يوم
     * 🧾 SIM2611101234
     * 📝 السبب: …                          (cancelled only; raw supplier reason, team only)
     */
    public function statusUpdateMessage(SimOrder $order): ?string
    {
        $order->loadMissing(['user', 'package']);
        $isBalanceProblem = SimOrder::isSupplierBalanceProblem($order->api_message);

        $title = match ($order->api_status) {
            SimOrder::API_SUCCESS => '✅ *' . __('messages.sim_group_update_loaded', [], 'ar') . '*',
            SimOrder::API_FAILED => $isBalanceProblem
                ? '🚨 *' . __('messages.sim_group_api_no_balance', ['details' => (string) $order->api_message], 'ar') . '*'
                : '❌ *' . __('messages.sim_group_update_cancelled', [], 'ar') . '*',
            default => null,
        };
        if ($title === null) {
            return null;
        }

        $lines = [
            $title,
            '',
            '👤 *' . ($order->user->name ?? '-') . '*',
            '```' . $order->phone_number . '```',
            '📦 ' . $this->shortPackageName($order),
            '🧾 ' . $order->order_number,
        ];

        if ($order->api_status === SimOrder::API_FAILED && !$isBalanceProblem && filled($order->api_message)) {
            $lines[] = '📝 ' . __('messages.sim_group_reason', [], 'ar') . ': ' . $order->api_message;
        }

        return implode("\n", $lines);
    }

    /**
     * 📶 *طلب باقة جديد - Tele CARD*
     *
     * 👤 *اسم المحل*
     * ```5051234567```          ← full number (with 50) in one copy block
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
            '```' . $order->phone_number . '```',
            '💰 ' . $t('sim_balance_value') . ': *' . SimPackage::formatPrice($order->price) . ' ₺*',
            '📦 ' . $this->shortPackageName($order),
        ];

        if ($order->customer_name) {
            $lines[] = '🙋 ' . $t('sim_customer_name_short') . ': ' . $order->customer_name;
        }
        if ($order->notes) {
            $lines[] = '📝 ' . $t('sim_notes_short') . ': ' . $order->notes;
        }

        // Automatic (kontor86) orders: tell the team NOT to load them by hand. Manual orders: unchanged.
        $apiLine = match ($order->api_status) {
            SimOrder::API_PROCESSING, SimOrder::API_SUCCESS => '🤖 ' . $t('sim_group_api_sent'),
            SimOrder::API_UNKNOWN => '⏳ ' . $t('sim_group_api_unknown'),
            SimOrder::API_FAILED => SimOrder::isSupplierBalanceProblem($order->api_message)
                ? '🚨 ' . __('messages.sim_group_api_no_balance', ['details' => (string) $order->api_message], 'ar')
                : '❌ ' . __('messages.sim_group_api_failed', ['reason' => (string) $order->api_message], 'ar'),
            default => null,
        };
        if ($apiLine) {
            $lines[] = '';
            $lines[] = $apiLine;
        }

        // The supplier raised its cost above our sale price: we would sell at a loss
        if ($order->api_cost !== null && (float) $order->api_cost > (float) $order->price) {
            $lines[] = '⚠️ ' . __('messages.sim_group_cost_above_price', [
                'cost' => SimPackage::formatPrice($order->api_cost),
                'price' => SimPackage::formatPrice($order->price),
            ], 'ar');
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
