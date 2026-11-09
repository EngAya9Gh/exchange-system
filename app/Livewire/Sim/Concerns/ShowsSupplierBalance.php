<?php

namespace App\Livewire\Sim\Concerns;

use App\Services\KontorApiService;
use Illuminate\Support\Facades\Cache;

/**
 * Our balance at kontor86, for SIM managers only (customers/agents never see it,
 * and no API call is made for them). Cached 60s; "refresh" forces a fresh call.
 */
trait ShowsSupplierBalance
{
    protected function supplierBalance(): ?array
    {
        if (!auth()->user()?->can('manage_sims')) {
            return null;
        }

        $api = app(KontorApiService::class);

        return $api->isConfigured() ? $api->cachedBalance() : null;
    }

    public function refreshSupplierBalance(): void
    {
        abort_unless((bool) auth()->user()?->can('manage_sims'), 403);
        Cache::forget(KontorApiService::BALANCE_CACHE_KEY);
    }
}
