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

    protected function post(string $message, SimOrder|string $context): bool
    {
        $groupId = (string) config('whatsapp.providers.wakeel.sim_group_id');

        if ($groupId === '' || empty(config('whatsapp.providers.wakeel.api_key'))) {
            $what = $context instanceof SimOrder ? 'order ' . $context->order_number : $context;
            Log::warning('SIM WhatsApp group not configured; ' . $what . ' was not posted.');
            return false;
        }

        return $this->whatsapp->provider('wakeel')->send($groupId, $message);
    }

    /**
     * Tells the group that automatic loading through one supplier was paused / resumed from the admin panel.
     * $nowVia: the supplier new orders go to while this one is paused (null = none → manual loads).
     */
    public function sendAutoSendNotice(string $supplier, bool $paused, string $byName, ?string $nowVia = null): bool
    {
        $text = match (true) {
            !$paused => __('messages.sim_group_auto_send_resumed', [], 'ar'),
            $nowVia !== null => __('messages.sim_group_auto_send_paused_switched', ['supplier' => $supplier, 'other' => $nowVia], 'ar'),
            default => __('messages.sim_group_auto_send_paused', [], 'ar'),
        };

        $message = ($paused ? '⏸️ *' : '▶️ *') . $text . '*'
            . "\n🔌 " . $supplier
            . "\n👤 " . $byName;

        return $this->post($message, 'auto-send notice');
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
        if ($order->api_supplier) {
            $lines[] = '🔌 ' . $order->api_supplier;
        }

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
        if ($order->client_reference) {
            $lines[] = '🔗 API: ' . $order->client_reference;
        }
        if ($order->notes) {
            $lines[] = '📝 ' . $t('sim_notes_short') . ': ' . $order->notes;
        }

        // Automatic orders: tell the team NOT to load them by hand (and through which supplier). Manual orders: unchanged.
        $via = $order->api_supplier ? ' · ' . $order->api_supplier : '';
        $apiLine = match ($order->api_status) {
            SimOrder::API_PROCESSING, SimOrder::API_SUCCESS => '🤖 ' . $t('sim_group_api_sent') . $via,
            SimOrder::API_UNKNOWN => '⏳ ' . $t('sim_group_api_unknown') . $via,
            // The supplier refused our product key: the paid order waits for a manual load
            null => match (true) {
                SimOrder::isSupplierProductProblem($order->api_message) => '⚙️ ' . __('messages.sim_group_api_fallback_manual', ['details' => (string) $order->api_message], 'ar'),
                // Would have loaded automatically, but every supplier holding its key is paused by an admin
                $this->pausedSuppliersFor($order) !== [] => '⏸️ ' . __('messages.sim_group_api_paused', ['suppliers' => implode('، ', $this->pausedSuppliersFor($order))], 'ar'),
                default => null,
            },
            SimOrder::API_FAILED => match (true) {
                SimOrder::isSupplierBalanceProblem($order->api_message) => '🚨 ' . __('messages.sim_group_api_no_balance', ['details' => (string) $order->api_message], 'ar'),
                SimOrder::isSupplierProductProblem($order->api_message) => '⚙️ ' . __('messages.sim_group_api_bad_product', ['details' => (string) $order->api_message], 'ar'),
                default => '❌ ' . __('messages.sim_group_api_failed', ['reason' => (string) $order->api_message], 'ar'),
            },
            default => null,
        };
        if ($apiLine) {
            $lines[] = '';
            $lines[] = $apiLine;
        }
        if ($order->api_supplier_forced && $order->api_supplier) {
            $lines[] = '🎯 ' . __('messages.sim_group_supplier_chosen', ['supplier' => $order->api_supplier], 'ar');
        }

        // Suppliers that refused first (their own problem): the team sees why, and gets 🚨 for an empty balance
        foreach ($order->apiRefusals() as $supplier => $reason) {
            if ($supplier === $order->api_supplier && $order->api_status !== SimOrder::API_PROCESSING && $order->api_status !== SimOrder::API_UNKNOWN) {
                continue; // already explained by the line above
            }
            $lines[] = SimOrder::isSupplierBalanceProblem($reason)
                ? '🚨 ' . __('messages.sim_group_supplier_low_balance', ['supplier' => $supplier], 'ar')
                : '↪️ ' . __('messages.sim_group_supplier_refused', ['supplier' => $supplier, 'details' => $reason], 'ar');
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

    /**
     * The package has a product key, but no supplier holding one is active: the paused ones among them
     * (e.g. ["kontor86"] for a Tele CARD package while kontor86 is paused). Empty = not a pause case.
     *
     * @return string[]
     */
    protected function pausedSuppliersFor(SimOrder $order): array
    {
        $package = $order->package;
        if (!$package) {
            return [];
        }

        $paused = [];
        foreach (\App\Services\KontorApiService::SUPPLIERS as $supplier) {
            if (!$package->isApiReadyFor($supplier)) {
                continue;
            }
            $api = \App\Services\KontorApiService::for($supplier);
            if ($api->autoSendActive()) {
                return [];
            }
            if ($api->isAutoSendPaused()) {
                $paused[] = $supplier;
            }
        }

        return $paused;
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
