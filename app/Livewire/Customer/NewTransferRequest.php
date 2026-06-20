<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\Transfer;
use App\Models\User;
use App\Notifications\NewTransferRequestNotification;
use App\Services\CommissionCalculator;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NewTransferRequest extends Component
{
    public $sender_name = '';
    public $sender_phone = '';
    public $destination = 'جميع المحافظات - فودافون مباشر';
    public $address = '';
    public $notes = '';
    public $recipient_name = '';
    public $recipient_phone = '';
    public $amount = 0;
    public $currency = 'TRY';

    // Computed values
    public $exchange_rate = 0.0;
    public $commission = 0.0;
    public $received_amount = 0.0;
    public $total_to_pay = 0.0;

    public function mount(): void
    {
        $this->calculateTotals();
    }

    public function autoSyncRates(): void
    {
        $dbRate = \App\Models\ExchangeRate::first();
        if (!$dbRate || $dbRate->updated_at->diffInHours(\Carbon\Carbon::now()) >= 1) {
            $rateService = app(ExchangeRateService::class);
            $rateService->syncAllRates();
            $this->calculateTotals();
        }
    }

    public function updatedAmount(): void
    {
        $this->calculateTotals();
    }

    public function updatedCurrency(): void
    {
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $rateService = app(ExchangeRateService::class);
        $commissionService = app(CommissionCalculator::class);

        $this->exchange_rate = $rateService->getRate($this->currency, 'EGP');

        if (empty($this->amount) || !is_numeric($this->amount) || $this->amount <= 0) {
            $this->commission = 0.0;
            $this->received_amount = 0.0;
            $this->total_to_pay = 0.0;
            return;
        }

        $this->commission = $commissionService->calculate((float)$this->amount);
        
        $this->received_amount = (float)$this->amount * $this->exchange_rate;
        $this->total_to_pay = (float)$this->amount + $this->commission;
    }

    public function submitRequest(): void
    {
        $this->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'destination' => 'required|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:10',
            'currency' => 'required|string|in:TRY,USD,EUR',
        ], [
            'recipient_name.required' => 'يرجى إدخال اسم المستفيد.',
            'recipient_phone.required' => 'يرجى إدخال رقم هاتف المستفيد.',
            'amount.min' => 'يجب ألا يقل مبلغ التحويل عن 10.',
        ]);

        $this->calculateTotals();

        $user = auth()->user();
        if ($user->balance < $this->total_to_pay) {
            $this->addError('amount', 'رصيدك الحالي غير كافٍ لإتمام هذه الحوالة.');
            return;
        }

        DB::beginTransaction();
        try {
            $transferNumber = 'RD' . time() . rand(100, 999);
            $transfer = Transfer::create([
                'transfer_number' => $transferNumber,
                'user_id' => $user->id,
                'sender_name' => $this->sender_name ?: null,
                'sender_phone' => $this->sender_phone ?: null,
                'recipient_name' => $this->recipient_name,
                'recipient_phone' => $this->recipient_phone,
                'destination' => $this->destination,
                'address' => $this->address,
                'notes' => $this->notes,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'target_currency' => 'EGP',
                'exchange_rate' => $this->exchange_rate,
                'commission' => $this->commission,
                'received_amount' => $this->received_amount,
                'net_amount' => $this->received_amount,
                'status' => 'pending',
            ]);

            // Deduct from user balance
            $user->balance -= $this->total_to_pay;
            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'حدث خطأ أثناء معالجة الطلب. يرجى المحاولة لاحقاً.');
            return;
        }

        // Notify all admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewTransferRequestNotification($transfer));
        }

        $this->reset(['sender_name', 'sender_phone', 'recipient_name', 'recipient_phone', 'destination', 'address', 'notes', 'amount']);
        $this->calculateTotals();

        session()->flash('success', 'تم إرسال طلب التحويل بنجاح! سيتم مراجعته من قبل الإدارة.');
        $this->dispatch('request-created');
        $this->js("window.open('/receipts/{$transferNumber}', '_blank');");
    }

    public function render()
    {
        return view('livewire.customer.new-transfer-request');
    }
}
