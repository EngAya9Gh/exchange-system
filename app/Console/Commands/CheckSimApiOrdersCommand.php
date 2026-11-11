<?php

namespace App\Console\Commands;

use App\Models\SimOrder;
use App\Services\SimOrderService;
use Illuminate\Console\Command;

/**
 * Mirrors kontor86 in our system on every scheduler run. Only unsettled / recent automatic loads are asked about:
 *  - open loads (accepted but not loaded yet, or unknown after a timeout / "8"): every run during their first hour,
 *    then every 15 minutes, for 3 days. Loaded → done; cancelled → refunded.
 *  - loads done in the last 24 h: once an hour, for a late cancellation by the supplier → refunded.
 * Nothing open → two small DB queries and no call to the supplier.
 */
class CheckSimApiOrdersCommand extends Command
{
    protected $signature = 'sims:check-api';

    protected $description = 'Check SIM package loads sent to kontor86 and refund the cancelled ones';

    public function handle(SimOrderService $service): int
    {
        $orders = SimOrder::whereIn('api_status', SimOrder::API_OPEN_STATES)
            ->whereNotNull('api_unique_id')
            ->where('created_at', '>=', now()->subDays(3)) // older ones are reviewed by hand in «طلبات الباقات»
            // First hour: every run. A load still open after that is asked about every 15 minutes.
            ->where(fn ($q) => $q->where('created_at', '>=', now()->subHour())
                ->orWhereNull('api_checked_at')
                ->orWhere('api_checked_at', '<=', now()->subMinutes(15)))
            ->orderBy('id')
            ->limit(50)
            ->get();

        // Already loaded, last 24 h: the supplier can still cancel / refund one later
        $loaded = SimOrder::where('api_status', SimOrder::API_SUCCESS)
            ->where('status', '!=', SimOrder::STATUS_REJECTED)
            ->whereNotNull('api_unique_id')
            ->where('created_at', '>=', now()->subDay())
            ->where(fn ($q) => $q->whereNull('api_checked_at')->orWhere('api_checked_at', '<=', now()->subHour()))
            ->orderBy('api_checked_at')
            ->limit(30)
            ->get();

        foreach ($orders->concat($loaded) as $order) {
            try {
                $state = $service->checkSupplierStatus($order);
                $this->line("{$order->order_number}: {$state}");
            } catch (\Throwable $e) {
                $this->error("{$order->order_number}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
