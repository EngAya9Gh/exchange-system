<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Transfer;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Notifications\TransferStatusNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class DeliverTransfer extends Component
{
    public string $transferNumber;
    public ?Transfer $transfer = null;
    public string $secretCode = '';
    public string $errorMessage = '';
    public bool $isSuccess = false;

    public function mount(string $number): void
    {
        $this->transferNumber = $number;
        $this->transfer = Transfer::where('transfer_number', $number)->firstOrFail();

        // Check if already delivered
        if ($this->transfer->status === 'received' || $this->transfer->status === 'paid') {
            $this->isSuccess = true;
            $this->errorMessage = 'هذه الحوالة تم تسليمها مسبقاً في ' . ($this->transfer->delivered_at ? $this->transfer->delivered_at->format('Y-m-d H:i:s') : 'تاريخ غير معروف');
        }
    }

    public function confirmDelivery(): void
    {
        $this->errorMessage = '';

        if (empty($this->secretCode)) {
            $this->errorMessage = 'يرجى إدخال الرقم السري.';
            return;
        }

        if ($this->transfer->secret_code !== $this->secretCode) {
            $this->errorMessage = 'الرقم السري غير صحيح!';
            return;
        }

        if ($this->transfer->status === 'received' || $this->transfer->status === 'paid') {
            $this->errorMessage = 'هذه الحوالة تم تسليمها بالفعل.';
            return;
        }

        // Lock in financial data if it was a pending request
        if ($this->transfer->status === 'pending') {
            $rateService = app(\App\Services\ExchangeRateService::class);
            $commissionService = app(\App\Services\CommissionCalculator::class);
            
            $rate = $rateService->getRate($this->transfer->currency, 'EGP');
            $commission = $commissionService->calculate((float) $this->transfer->amount);
            $received = $this->transfer->amount * $rate;
            
            $this->transfer->update([
                'target_currency' => 'EGP',
                'exchange_rate' => $rate,
                'received_amount' => $received,
                'commission' => $commission,
                'net_amount' => $received,
            ]);
        }

        $this->transfer->update([
            'status' => 'received',
            'paid_by' => auth()->id(),
            'delivered_at' => Carbon::now(),
            'admin_notes' => trim($this->transfer->admin_notes . "\nتم التسليم عبر نظام الباركود السريع."),
        ]);

        // Notify client and admin
        try {
            if ($this->transfer->user_id) {
                $this->transfer->user->notify(new TransferStatusNotification($this->transfer, 'paid'));
            }
            if (auth()->check()) {
                auth()->user()->notify(new TransferStatusNotification($this->transfer, 'paid'));
            }
        } catch (\Exception $e) {
            Log::error("Failed to notify user on barcode transfer pay: " . $e->getMessage());
        }

        $this->isSuccess = true;
        session()->flash('success_message', 'تم التسليم بنجاح.');
    }

    public function render()
    {
        return view('livewire.admin.deliver-transfer');
    }
}
