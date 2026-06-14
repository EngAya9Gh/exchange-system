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
        // 1. Try to find an active tier for the amount
        $tier = CommissionTier::where('status', 'active')
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if ($tier) {
            if ($tier->commission_type === 'percentage') {
                return $amount * ((float) $tier->commission_value / 100);
            }
            return (float) $tier->commission_value;
        }

        // 3. Default fallback commission: from Settings
        $defaultSetting = \App\Models\Setting::where('key', 'default_commission_percentage')->first();
        $defaultPercentage = $defaultSetting ? (float) $defaultSetting->value : 2.0;

        return $amount * ($defaultPercentage / 100);
    }
}
