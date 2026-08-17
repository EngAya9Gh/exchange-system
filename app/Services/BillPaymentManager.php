<?php

namespace App\Services;

use App\Models\BillPayment;
use App\Models\BalanceTransaction;
use App\Models\User;
use App\Notifications\ApiBalanceCriticalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BillPaymentManager
{
    protected FaturaApiService $apiService;

    public function __construct(FaturaApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Process a new bill payment request.
     * Returns an array with 'success', 'message', and optionally the 'bill' object.
     */
    public function processPayment(User $user, int $kurumId, string $aboneNo, float $amount, string $faturaNo = '', ?string $billInquiryId = null): array
    {
        // 1. Calculate Commission based on Billing Settings
        $systemCommission = 0.0;
        
        $isAdmin = $user->hasRole('Super Admin') || $user->role === 'admin';
        
        if (!$isAdmin) {
            $enableSetting = \App\Models\Setting::where('key', 'billing_enable_automated_commissions')->first();
            $automatedEnabled = $enableSetting ? filter_var($enableSetting->value, FILTER_VALIDATE_BOOLEAN) : true;

            if ($automatedEnabled) {
                if ($user->hasRole('Agent')) {
                    $agentSetting = \App\Models\Setting::where('key', 'billing_agent_commission_percentage')->first();
                    $percentage = $agentSetting ? (float) $agentSetting->value : 0.5;
                } elseif ($user->hasRole('Customer')) {
                    $customerSetting = \App\Models\Setting::where('key', 'billing_customer_commission_percentage')->first();
                    $percentage = $customerSetting ? (float) $customerSetting->value : 1.0;
                } else {
                    $defaultSetting = \App\Models\Setting::where('key', 'billing_default_commission_percentage')->first();
                    $percentage = $defaultSetting ? (float) $defaultSetting->value : 2.0;
                }
                
                $systemCommission = $amount * ($percentage / 100);
            }
        }

        $totalToDeduct = $amount + $systemCommission;

        // 2. Check Balance (for non-admins)
        $availableCredit = $user->has_unlimited_balance ? PHP_FLOAT_MAX : ($user->balance + $user->balance_limit);

        if (!$isAdmin && $totalToDeduct > $availableCredit) {
            return ['success' => false, 'message' => 'رصيدك غير كافٍ لإتمام دفع هذه الفاتورة.'];
        }

        // 3. Generate unique transaction ID for API
        $tahsilatApiIslemId = 'BILL_' . time() . '_' . rand(1000, 9999);

        DB::beginTransaction();
        try {
            // 4. Create BillPayment Record (Pending)
            $bill = BillPayment::create([
                'user_id' => $user->id,
                'kurum_id' => $kurumId,
                'abone_no' => $aboneNo,
                'fatura_no' => $faturaNo,
                'amount' => $amount,
                'commission' => $systemCommission,
                'total_deducted' => $totalToDeduct,
                'tahsilat_api_islem_id' => $tahsilatApiIslemId,
                'api_status' => 'pending',
                'paid_by' => auth()->id() ?? $user->id,
            ]);

            // 5. Deduct Balance and Log
            if (!$isAdmin) {
                $user->balance -= $totalToDeduct;
                $user->save();

                BalanceTransaction::create([
                    'user_id' => $user->id,
                    'admin_id' => auth()->id() ?? $user->id,
                    'amount' => -$totalToDeduct,
                    'balance_before' => $user->balance + $totalToDeduct,
                    'balance_after' => $user->balance,
                    'type' => 'payment', // using payment for bills
                    'notes' => 'خصم فاتورة (المرجع: ' . $tahsilatApiIslemId . ')',
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("BillPayment DB Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في النظام أثناء حفظ الفاتورة.'];
        }

        // 6. Call External API
        $apiResponse = $this->apiService->faturaEkle([
            'kurum_id' => $kurumId,
            'tahsilat_api_islem_id' => $tahsilatApiIslemId,
            'abone_no' => $aboneNo,
            'fatura_no' => $faturaNo,
            'amount' => $amount,
            'bill_inquiry_id' => $billInquiryId,
        ]);

        if ($apiResponse['success']) {
            // API accepted it, it is now pending or processed. Update the API cost.
            $bill->update([
                'api_cost' => $apiResponse['cost'],
                'api_status_message' => 'API Accepted (Cost: ' . $apiResponse['cost'] . ')',
            ]);
            
            return ['success' => true, 'message' => 'تم إرسال الفاتورة بنجاح. وحالتها الآن قيد المعالجة.', 'bill' => $bill];
        } else {
            // Check if failure is due to insufficient API balance (system-level issue, not user fault)
            if (!empty($apiResponse['insufficient_api_balance'])) {
                // Parse current balance from the API error message
                // e.g. "Balance in your account : 100.0000"
                $currentBalance = 0.0;
                if (preg_match('/Balance in your account\s*:\s*([\d.]+)/i', $apiResponse['message_en'] ?? '', $m)) {
                    $currentBalance = (float) $m[1];
                }

                // Refund the user first (it's not their fault)
                $this->refundBill($bill, 'رصيد API غير كافٍ - الخدمة غير متاحة مؤقتاً');

                // Notify ALL Super Admins via database notification + Telegram
                $admins = User::role('Super Admin')->get();
                foreach ($admins as $admin) {
                    try {
                        $admin->notify(new ApiBalanceCriticalNotification(
                            requiredAmount: (float) $bill->amount,
                            currentBalance: $currentBalance,
                            kurumId:        $kurumId,
                            aboneNo:        $aboneNo
                        ));
                    } catch (\Exception $e) {
                        Log::error('Failed to send ApiBalanceCriticalNotification: ' . $e->getMessage());
                    }
                }

                Log::critical('⚠️ API BALANCE CRITICAL: رصيد BayiWebPanel غير كافٍ', [
                    'required' => $bill->amount,
                    'current'  => $currentBalance,
                    'kurum_id' => $kurumId,
                    'abone_no' => $aboneNo,
                ]);

                return [
                    'success'              => false,
                    // User sees a friendly message (no technical details)
                    'message'              => 'عذراً، الخدمة غير متاحة حالياً. تم استرداد رصيدك كاملاً. يرجى المحاولة لاحقاً أو التواصل مع الدعم.',
                    'error_code'           => '0110',
                    'is_api_balance_issue' => true,
                ];
            }

            // Other API failures (duplicate, invalid institution, etc.)
            Log::error('❌ فشل دفع الفاتورة من المزود:', [
                'bill_id' => $bill->id,
                'tahsilat_api_islem_id' => $tahsilatApiIslemId,
                'abone_no' => $aboneNo,
                'kurum_id' => $kurumId,
                'amount' => $amount,
                'api_response' => $apiResponse
            ]);

            $this->refundBill($bill, 'فشل فوري من مزود الخدمة: ' . $apiResponse['message']);

            return ['success' => false, 'message' => 'فشل الدفع: ' . $apiResponse['message']];
        }
    }

    /**
     * Refund a failed bill payment
     */
    public function refundBill(BillPayment $bill, string $reason): void
    {
        if ($bill->api_status === 'refunded' || $bill->api_status === 'failed') {
            return; // already refunded
        }

        DB::beginTransaction();
        try {
            $bill->update([
                'api_status' => 'failed', // or refunded
                'api_status_message' => $reason,
            ]);

            $user = $bill->user;
            $isAdmin = $user->hasRole('Super Admin') || $user->role === 'admin';

            if (!$isAdmin) {
                $refundAmount = $bill->total_deducted;
                $user->balance += $refundAmount;
                $user->save();

                BalanceTransaction::create([
                    'user_id' => $user->id,
                    'admin_id' => auth()->id() ?? $user->id,
                    'amount' => $refundAmount,
                    'balance_before' => $user->balance - $refundAmount,
                    'balance_after' => $user->balance,
                    'type' => 'deposit',
                    'notes' => 'استرداد قيمة فاتورة فاشلة (المرجع: ' . $bill->tahsilat_api_islem_id . ')',
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("BillPayment Refund Error: " . $e->getMessage());
        }
    }
}
