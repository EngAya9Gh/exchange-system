<?php

namespace App\Livewire\Sim\Concerns;

use App\Services\KontorApiService;
use Illuminate\Support\Facades\Cache;

/**
 * Our balance at each connected supplier (kontor86, telekomtl), for SIM managers only:
 * customers/agents never see it, and no API call is made for them. Cached 60s; "refresh" forces fresh calls.
 */
trait ShowsSupplierBalance
{
    /**
     * @return array<string, array{supplier: string, primary: bool, ok: bool, balance: ?float, error: ?string}>
     *         configured suppliers only, the primary one first
     */
    protected function supplierBalances(): array
    {
        if (!auth()->user()?->can('manage_sims')) {
            return [];
        }

        $primary = KontorApiService::primarySupplier();
        $balances = [];

        foreach (KontorApiService::inPriorityOrder() as $supplier => $api) {
            if ($api->isConfigured()) {
                $balances[$supplier] = $api->cachedBalance() + ['supplier' => $supplier, 'primary' => $supplier === $primary];
            }
        }

        return $balances;
    }

    public function refreshSupplierBalance(): void
    {
        abort_unless((bool) auth()->user()?->can('manage_sims'), 403);

        foreach (KontorApiService::SUPPLIERS as $supplier) {
            Cache::forget(KontorApiService::for($supplier)->balanceCacheKey());
        }
    }
}
