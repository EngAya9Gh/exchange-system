<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CommissionTier;

class CommissionCalculator
{
    /**
     * Calculate commission for a given amount and region.
     *
     * @param float $amount
     * @param int|null $regionId
     * @return float
     */
    public function calculate(float $amount, ?int $regionId = null): float
    {
        $user = auth()->user();

        // Admin always 0
        if ($user && ($user->hasRole('Super Admin') || $user->role === 'admin')) {
            return 0.0;
        }

        // Determine user role for tier matching
        $userRole = 'customer';
        if ($user && $user->hasRole('Agent')) {
            $userRole = 'agent';
        }

        // 1. Check Commission Tiers first
        $tier = CommissionTier::where('status', 'active')
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->whereIn('target_role', ['all', $userRole])
            ->orderByRaw("target_role = 'all' ASC") // Prioritize specific role (agent/customer) over 'all'
            ->first();

        if ($tier) {
            if ($tier->commission_type === 'fixed') {
                return (float) $tier->commission_value;
            }
            return $amount * ((float) $tier->commission_value / 100);
        }

        // 2. Fallback to percentage settings if no tier is found
        if ($user) {
            // Agent fallback
            if ($user->hasRole('Agent')) {
                $agentSetting = \App\Models\Setting::where('key', 'agent_commission_percentage')->first();
                $agentPercentage = $agentSetting ? (float) $agentSetting->value : 0.5;
                return $amount * ($agentPercentage / 100);
            }

            // Customer fallback
            if ($user->hasRole('Customer')) {
                $customerSetting = \App\Models\Setting::where('key', 'customer_commission_percentage')->first();
                $customerPercentage = $customerSetting ? (float) $customerSetting->value : 1.0;
                return $amount * ($customerPercentage / 100);
            }
        }

        // Default fallback
        $defaultSetting = \App\Models\Setting::where('key', 'default_commission_percentage')->first();
        $defaultPercentage = $defaultSetting ? (float) $defaultSetting->value : 1.0;
        return $amount * ($defaultPercentage / 100);
    }
}
