<?php

namespace App\Services;

use App\Exceptions\SimOrderException;
use App\Models\BalanceTransaction;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Models\User;
use App\Notifications\NewSimOrderNotification;
use App\Notifications\SimOrderStatusNotification;
use App\Services\WhatsApp\SimOrderGroupNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SIM / internet package sales.
 *
 * Like auto-accepted Vodafone Cash transfers: the order is paid from the user's balance
 * and marked "completed" (activated) immediately.
 *
 *  - Packages with an API product key (operator + tip + code) are sent to kontor86 right away:
 *    refused → refunded immediately; accepted / unknown → tracked by sims:check-api until the
 *    supplier confirms (success) or cancels (refund). Never refunded on a timeout.
 *  - Other packages stay manual: the "Tele CARD" WhatsApp group gets the order and the team
 *    loads it on the supplier panel; if that fails a manager cancels & refunds.
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
                'network_name' => $package->network_name,
                'price' => $price,
                'charged_amount' => $isExempt ? 0 : $price,
                'phone_number' => $phoneNumber,
                'customer_name' => $customerName ?: null,
                'notes' => $notes ?: null,
                'status' => SimOrder::STATUS_COMPLETED, // activated immediately
                'processed_at' => now(),
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

        $this->sendToSupplier($order);
        $order->refresh();

        $this->notifyManagers($order);

        return $order;
    }

    /**
     * Sends the order to kontor86 when its package has an API product key.
     * Manual packages (or no API credentials) are left untouched.
     */
    public function sendToSupplier(SimOrder $order): void
    {
        $package = $order->package;
        $api = app(KontorApiService::class);

        if (!$package || !$package->isApiReady() || !$api->isConfigured()) {
            return;
        }

        // tekilnumara must be digits only; the order number is unique ("SIM2611101234" → "2611101234")
        $uniqueId = preg_replace('/\D+/', '', (string) $order->order_number);

        // Marked BEFORE the call: if anything breaks mid-request we still know it must be checked
        $order->update(['api_status' => SimOrder::API_UNKNOWN, 'api_unique_id' => $uniqueId]);

        try {
            $result = $api->sendTopUp(
                (string) $package->api_operator,
                (string) $package->api_tip,
                (string) $package->api_code,
                (string) $order->phone_number,
                $uniqueId
            );
        } catch (\Throwable $e) {
            Log::error('kontor86 send failed for SIM order ' . $order->order_number . ': ' . $e->getMessage());
            $order->update(['api_message' => mb_substr($e->getMessage(), 0, 500), 'api_checked_at' => now()]);
            return;
        }

        if ($result['ok']) {
            $order->update([
                'api_status' => SimOrder::API_PROCESSING,
                'api_cost' => $result['cost'],
                'api_message' => $result['message'],
                'api_checked_at' => now(),
            ]);
            return;
        }

        if ($result['status'] === KontorApiService::SEND_FAILED) {
            // Definite refusal: nothing was loaded → refund now (the buyer sees the error on screen)
            $order->update(['api_status' => SimOrder::API_FAILED, 'api_message' => $result['message'], 'api_checked_at' => now()]);
            // admin_notes is visible to the buyer → customer-safe reason; the raw one stays in api_message (team only)
            $this->reject($order, null, __('messages.sim_api_refused_reason', ['reason' => SimOrder::publicSupplierReason($result['message'])], 'ar'), false);
            return;
        }

        // 8 / no answer / undocumented answer: we don't know → check later, never refund blindly
        $order->update(['api_message' => $result['message'], 'api_checked_at' => now()]);
    }

    /**
     * Asks kontor86 about an automatic load and mirrors the answer in our system:
     *  - still open (processing / unknown): loaded → done, buyer + group told; cancelled → refund, buyer + group told.
     *  - loaded in the last 24 h (re-checked by sims:check-api): only a later cancellation is applied → refund, buyer + group told.
     * Every change is a guarded transition, so the cron and the «تحقق الآن» button can never apply it twice.
     *
     * @return string the order's api_status after the check
     */
    public function checkSupplierStatus(SimOrder $order): string
    {
        $isOpen = in_array($order->api_status, SimOrder::API_OPEN_STATES, true);
        $isLoaded = $order->api_status === SimOrder::API_SUCCESS && $order->status !== SimOrder::STATUS_REJECTED;

        if ((!$isOpen && !$isLoaded) || blank($order->api_unique_id)) {
            return (string) $order->api_status;
        }

        $result = app(KontorApiService::class)->checkTopUp((string) $order->api_unique_id);
        $from = $isOpen ? SimOrder::API_OPEN_STATES : [SimOrder::API_SUCCESS];

        if ($result['state'] === 'success' && $isOpen) {
            $changes = ['api_status' => SimOrder::API_SUCCESS, 'api_cost' => $result['cost'] ?? $order->api_cost];
            if ($this->moveApiStatus($order, $from, $changes)) {
                $order->refresh();
                $this->notifyOwner($order, SimOrder::STATUS_COMPLETED);
                $this->postGroupUpdate($order);
            }
        } elseif ($result['state'] === 'cancelled') {
            $reason = $result['reason'] ?: '-';
            if ($this->moveApiStatus($order, $from, ['api_status' => SimOrder::API_FAILED, 'api_message' => $reason])) {
                $order->refresh();
                if ($order->status !== SimOrder::STATUS_REJECTED) {
                    try {
                        $this->reject($order, null, __('messages.sim_api_cancelled_reason', ['reason' => SimOrder::publicSupplierReason($reason)], 'ar'));
                    } catch (SimOrderException) {
                        // A manager cancelled & refunded it at the same moment: nothing left to refund
                    }
                }
                $this->postGroupUpdate($order->fresh());
            }
        } elseif ($result['state'] === 'processing' && $isOpen) {
            $this->moveApiStatus($order, $from, ['api_status' => SimOrder::API_PROCESSING]);
        } else {
            $order->update(['api_checked_at' => now()]);
        }

        return (string) $order->fresh()->api_status;
    }

    /** Changes api_status only while it is still one of $from (atomic). True when this call made the change. */
    protected function moveApiStatus(SimOrder $order, array $from, array $changes): bool
    {
        return SimOrder::whereKey($order->id)
            ->whereIn('api_status', $from)
            ->update($changes + ['api_checked_at' => now()]) === 1;
    }

    /** Tells the "Tele CARD" group how an automatic load ended (loaded / cancelled by the supplier). */
    protected function postGroupUpdate(SimOrder $order): void
    {
        try {
            app(SimOrderGroupNotifier::class)->sendStatusUpdate($order);
        } catch (\Throwable $e) {
            Log::error('Failed to post SIM order update ' . $order->order_number . ' to WhatsApp group: ' . $e->getMessage());
        }
    }

    /**
     * Confirms a legacy "pending" order (placed before instant activation).
     *
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
     * Rejects / cancels a pending or activated order and refunds exactly what was charged
     * (e.g. loading on the supplier panel failed). The row lock + status check guarantee
     * the refund can never happen twice.
     *
     * @throws SimOrderException when the order was already rejected
     */
    public function reject(SimOrder $order, ?User $admin, string $reason, bool $notifyOwner = true): SimOrder
    {
        $order = DB::transaction(function () use ($order, $admin, $reason) {
            $locked = SimOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === SimOrder::STATUS_REJECTED) {
                throw new SimOrderException(__('messages.sim_order_already_processed'));
            }

            $locked->update([
                'status' => SimOrder::STATUS_REJECTED,
                'admin_notes' => $reason,
                'processed_by' => $admin?->id,
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
                    'admin_id' => $admin?->id ?? auth()->id() ?? $owner->id,
                    'type' => 'deposit',
                    'amount' => $refund,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $owner->balance,
                    'notes' => 'استرداد قيمة باقة مرفوضة (طلب ' . $locked->order_number . ')',
                ]);
            }

            return $locked;
        });

        if ($notifyOwner) {
            $this->notifyOwner($order, SimOrder::STATUS_REJECTED);
        }

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

        // One WhatsApp message to the "Tele CARD" group (like the Vodafone Cash transfers group)
        try {
            app(SimOrderGroupNotifier::class)->sendNewOrder($order);
        } catch (\Throwable $e) {
            Log::error('Failed to post SIM order ' . $order->order_number . ' to WhatsApp group: ' . $e->getMessage());
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
