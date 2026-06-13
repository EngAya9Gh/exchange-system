<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\Branch;
use App\Models\Region;
use App\Models\TransferRequest;
use App\Services\CommissionCalculator;
use App\Services\ExchangeRateService;
use Livewire\Component;

class NewTransferRequest extends Component
{
    public string $sender_name = '';
    public string $sender_phone = '';
    public string $recipient_name = '';
    public string $recipient_phone = '';
    public float $amount = 0;
    public string $currency = 'TRY';
    public ?int $region_id = null;
    public ?int $branch_id = null;

    // Computed values
    public float $exchange_rate = 0.0;
    public float $commission = 0.0;
    public float $received_amount = 0.0;
    public float $total_to_pay = 0.0;

    public array $regions = [];
    public array $branches = [];

    public function mount(): void
    {
        $this->regions = Region::where('status', 'active')->get()->toArray();
        if (count($this->regions) > 0) {
            $this->region_id = (int) $this->regions[0]['id'];
            $this->updatedRegionId();
        }
        $this->calculateTotals();
    }

    public function updatedRegionId(): void
    {
        $this->branches = Branch::where('region_id', $this->region_id)
            ->where('status', 'active')
            ->get()
            ->toArray();
            
        $this->branch_id = count($this->branches) > 0 ? (int) $this->branches[0]['id'] : null;
        $this->calculateTotals();
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
        if ($this->amount <= 0) {
            $this->exchange_rate = 0.0;
            $this->commission = 0.0;
            $this->received_amount = 0.0;
            $this->total_to_pay = 0.0;
            return;
        }

        $rateService = app(ExchangeRateService::class);
        $commissionService = app(CommissionCalculator::class);

        $this->exchange_rate = $rateService->getRate($this->currency, 'EGP', $this->region_id);
        $this->commission = $commissionService->calculate($this->amount, $this->region_id);
        
        $this->received_amount = $this->amount * $this->exchange_rate;
        $this->total_to_pay = $this->amount + $this->commission;
    }

    public function submitRequest(): void
    {
        $this->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'amount' => 'required|numeric|min:10',
            'currency' => 'required|string|in:TRY,USD,EUR',
            'region_id' => 'required|exists:regions,id',
            'branch_id' => 'required|exists:branches,id',
        ], [
            'sender_name.required' => 'يرجى إدخال اسم المرسل.',
            'sender_phone.required' => 'يرجى إدخال رقم هاتف المرسل.',
            'recipient_name.required' => 'يرجى إدخال اسم المستفيد.',
            'recipient_phone.required' => 'يرجى إدخال رقم هاتف المستفيد.',
            'amount.min' => 'يجب ألا يقل مبلغ التحويل عن 10.',
            'branch_id.required' => 'يرجى تحديد فرع استلام الحوالة.',
        ]);

        TransferRequest::create([
            'user_id' => auth()->id(),
            'sender_name' => $this->sender_name,
            'sender_phone' => $this->sender_phone,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => 'pending',
        ]);

        $this->reset(['sender_name', 'sender_phone', 'recipient_name', 'recipient_phone', 'amount']);
        $this->calculateTotals();

        session()->flash('success', 'تم إرسال طلب التحويل بنجاح! سيتم مراجعته من قبل الإدارة.');
        $this->dispatch('request-created');
    }

    public function render()
    {
        return view('livewire.customer.new-transfer-request');
    }
}
