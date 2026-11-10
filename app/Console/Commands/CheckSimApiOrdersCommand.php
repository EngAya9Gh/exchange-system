<?php

namespace App\Console\Commands;

use App\Models\SimOrder;
use App\Services\SimOrderService;
use Illuminate\Console\Command;

/**
 * Follows up SIM orders sent to kontor86 whose outcome is still open
 * (accepted but not yet loaded, or unknown after a timeout / "8" answer).
 * Success → marked loaded; cancelled by the supplier → refunded and the buyer is told.
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
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($orders as $order) {
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
