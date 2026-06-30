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

        // التحقق من صلاحيات المستخدم وتطبيق النسبة المئوية للعمولة
        if ($user) {
            // مدير النظام: 0% بدون أجور
            if ($user->hasRole('Super Admin') || $user->role === 'admin') {
                return 0.0;
            }

            // موظف فرع / وكيل
            if ($user->hasRole('Agent')) {
                $agentSetting = \App\Models\Setting::where('key', 'agent_commission_percentage')->first();
                $agentPercentage = $agentSetting ? (float) $agentSetting->value : 0.5;
                return $amount * ($agentPercentage / 100);
            }

            // زبون عادي
            if ($user->hasRole('Customer')) {
                $customerSetting = \App\Models\Setting::where('key', 'customer_commission_percentage')->first();
                $customerPercentage = $customerSetting ? (float) $customerSetting->value : 1.0;
                return $amount * ($customerPercentage / 100);
            }
        }

        // القيمة الافتراضية
        $defaultSetting = \App\Models\Setting::where('key', 'default_commission_percentage')->first();
        $defaultPercentage = $defaultSetting ? (float) $defaultSetting->value : 1.0;
        return $amount * ($defaultPercentage / 100);
    }
}
