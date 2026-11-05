<?php

namespace App\Services;

use App\Exceptions\SimOrderException;
use App\Models\BalanceTransaction;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Models\User;
use App\Notifications\NewSimOrderNotification;
use App\Notifications\SimOrderStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SIM / internet package sales.
 *
 * Fulfilment is manual: the order is paid from the user's balance immediately and
 * stays "pending" until an admin activates it on the supplier panel and marks it
 * completed — or rejects it, which refunds exactly what was charged.
 */
class SimOrderService
{
    /**
     * @throws SimOrderException when the package is unavailable or the balance is insufficient
     */
    public function placeOrder(
        User $user,
        int $packageId,
        string $phoneNumber,
        ?string $customerName = null,
        ?string $notes = null
    ): SimOrder {
        $order = DB::transaction(function () use ($user, $packageId, $phoneNumber, $customerName, $notes) {
            $package = SimPackage::active()->find($packageId);
            if (!$package) {
                throw new SimOrderException(__('messages.sim_package_unavailable'));
            }

            // Lock the user row so two simultaneous orders can't both pass the balance check
            $buyer = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $isExempt = $this->isExemptFromCharge($buyer);
            $price = (float) $package->price;

            if (!$isExempt) {
                $availableCredit = $buyer->has_unlimited_balance
                    ? PHP_FLOAT_MAX
                    : ((float) $buyer->balance + (float) $buyer->balance_limit);

                if ($price > $availableCredit) {
                    throw new SimOrderException(__('messages.sim_insufficient_balance'));
                }
            }

            $order = SimOrder::create([
                'user_id' => $buyer->id,
                'sim_package_id' => $package->id,
                'package_name_ar' => $package->name_ar,
                'package_name_tr' => $package->name_tr,
                'region' => $package->region,
                'sim_type' => $package->sim_type,
                'price' => $price,
                'charged_amount' => $isExempt ? 0 : $price,
                'phone_number' => $phoneNumber,
                'customer_name' => $customerName ?: null,
                'notes' => $notes ?: null,
                'status' => SimOrder::STATUS_PENDING,
            ]);

            if (!$isExempt) {
                $balanceBefore = (float) $buyer->balance;
                $buyer->balance = $balanceBefore - $price;
                $buyer->save();

                BalanceTransaction::create([
                    'user_id' => $buyer->id,
                    'admin_id' => auth()->id() ?? $buyer->id,
                    'type' => 'withdrawal',
                    'amount' => -$price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $buyer->balance,
                    'notes' => 'شراء باقة: ' . $package->name_ar . ' (طلب ' . $order->order_number . ')',
                ]);
            }

            return $order;
        });

        $this->notifyManagers($order);

        return $order;
    }

    /**
     * @throws SimOrderException when the order was already processed
     */
    public function complete(SimOrder $order, User $admin, ?string $adminNotes = null): SimOrder
    {
        $order = DB::transaction(function () use ($order, $admin, $adminNotes) {
            $locked = SimOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (!$locked->isPending()) {
                throw new SimOrderException(__('messages.sim_order_already_processed'));
            }

            $locked->update([
                'status' => SimOrder::STATUS_COMPLETED,
                'admin_notes' => $adminNotes ?: null,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            return $locked;
        });

        $this->notifyOwner($order, SimOrder::STATUS_COMPLETED);

        return $order;
    }

    /**
     * Rejects a pending order and refunds exactly what was charged.
     * The row lock + status check guarantee the refund can never happen twice.
     *
     * @throws SimOrderException when the order was already processed
     */
    public function reject(SimOrder $order, User $admin, string $reason): SimOrder
    {
        $order = DB::transaction(function () use ($order, $admin, $reason) {
            $locked = SimOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (!$locked->isPending()) {
                throw new SimOrderException(__('messages.sim_order_already_processed'));
            }

            $locked->update([
                'status' => SimOrder::STATUS_REJECTED,
                'admin_notes' => $reason,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $refund = (float) $locked->charged_amount;
            if ($refund > 0) {
                $owner = User::withTrashed()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
                $balanceBefore = (float) $owner->balance;
                $owner->balance = $balanceBefore + $refund;
                $owner->save();

                BalanceTransaction::create([
                    'user_id' => $owner->id,
                    'admin_id' => $admin->id,
                    'type' => 'deposit',
                    'amount' => $refund,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $owner->balance,
                    'notes' => 'استرداد قيمة باقة مرفوضة (طلب ' . $locked->order_number . ')',
                ]);
            }

            return $locked;
        });

        $this->notifyOwner($order, SimOrder::STATUS_REJECTED);

        return $order;
    }

    /** Same rule as bill payments: Super Admin orders are not charged to a balance. */
    public function isExemptFromCharge(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->role === 'admin';
    }

    protected function notifyManagers(SimOrder $order): void
    {
        try {
            $managers = User::permission('manage_sims')
                ->where('id', '!=', $order->user_id)
                ->get();

            foreach ($managers as $manager) {
                $manager->notify((new NewSimOrderNotification($order))->locale($this->localeFor($manager)));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to notify managers about SIM order ' . $order->order_number . ': ' . $e->getMessage());
        }
    }

    protected function notifyOwner(SimOrder $order, string $status): void
    {
        try {
            $owner = $order->user()->withTrashed()->first();
            if ($owner && !$owner->trashed()) {
                $owner->notify((new SimOrderStatusNotification($order, $status))->locale($this->localeFor($owner)));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to notify owner about SIM order ' . $order->order_number . ': ' . $e->getMessage());
        }
    }

    protected function localeFor(User $user): string
    {
        return in_array($user->language, ['ar', 'tr', 'en'], true) ? $user->language : 'ar';
    }
}
