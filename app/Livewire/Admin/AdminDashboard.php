<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\CommissionTier;
use App\Models\ExchangeRate;
use App\Models\Transfer;
use App\Services\CommissionCalculator;
use App\Services\ExchangeRateService;
use App\Services\SecretCodeGenerator;
use App\Services\ReceiptService;
use App\Notifications\TransferStatusNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Livewire\Actions\Logout;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class AdminDashboard extends Component
{
    use WithPagination;

    // Tabs state
    #[Url(history: true)]
    public string $activeTab = 'dashboard'; // dashboard | new_transfer | ledger | rates | requests | commissions

    // Manual form state
    public $sender_name = '';
    public $sender_phone = '';
    public $destination = 'جميع المحافظات - فودافون مباشر';
    public $address = '';
    public $notes = '';
    public $recipient_name = '';
    public $recipient_phone = '';
    public $amount = 0;
    public $source_currency = 'TRY';
    public $target_currency = 'EGP';
    public $exchange_rate = 0.0;
    public $commission = 0.0;
    public $received_amount = 0.0;
    public $total_to_pay = 0.0;
    public $manual_fee = ''; // Manual wages

    // Search and filter in ledger
    public string $searchQuery = '';
    public string $ledgerStatusFilter = 'all';

    // Rate adjustment state
    public array $adjustedRates = [];

    // Receipt modal state
    public ?Transfer $selectedTransfer = null;
    public bool $showReceiptModal = false;
    public string $receiptPdfUrl = '';

    // Commissions state
    public bool $enableAutomatedCommissions = true;
    public float $defaultCommission = 2.0;
    public $tierMinAmount = '';
    public $tierMaxAmount = '';
    public $tierCommissionType = 'fixed';
    public $tierCommissionValue = '';

    public function mount(): void
    {
        $this->calculateTotals();
        $this->loadRates();

        $enableSetting = \App\Models\Setting::where('key', 'enable_automated_commissions')->first();
        $this->enableAutomatedCommissions = $enableSetting ? filter_var($enableSetting->value, FILTER_VALIDATE_BOOLEAN) : true;

        $defaultSetting = \App\Models\Setting::where('key', 'default_commission_percentage')->first();
        $this->defaultCommission = $defaultSetting ? (float) $defaultSetting->value : 2.0;
        
        $this->calculateTotals();
    }

    public function updatedAmount(): void
    {
        $this->calculateTotals();
    }

    public function updatedManualFee(): void
    {
        if (!$this->enableAutomatedCommissions) {
            $this->calculateTotals();
        }
    }

    public function autoSyncRates(): void
    {
        $dbRate = \App\Models\ExchangeRate::first();
        // If rates haven't been updated in the last hour, sync them via Ajax
        if (!$dbRate || $dbRate->updated_at->diffInHours(\Carbon\Carbon::now()) >= 1) {
            $rateService = app(ExchangeRateService::class);
            $rateService->syncAllRates();
            $this->loadRates();
            $this->calculateTotals();
        }
    }

    public function updatedSourceCurrency(): void
    {
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $rateService = app(ExchangeRateService::class);
        $commissionService = app(CommissionCalculator::class);

        // Always fetch the exchange rate regardless of amount
        $this->exchange_rate = $rateService->getRate($this->source_currency, $this->target_currency);

        if (empty($this->amount) || !is_numeric($this->amount) || $this->amount <= 0) {
            $this->commission = 0.0;
            $this->received_amount = 0.0;
            $this->total_to_pay = 0.0;
            return;
        }

        if ($this->enableAutomatedCommissions) {
            $this->commission = $commissionService->calculate((float)$this->amount);
        } else {
            $this->commission = empty($this->manual_fee) ? 0.0 : (float)$this->manual_fee;
        }
        
        $this->received_amount = (float)$this->amount * $this->exchange_rate;
        $this->total_to_pay = (float)$this->amount + $this->commission;
    }

    // Load exchange rates for edit
    public function loadRates(): void
    {
        $rates = ExchangeRate::all();
        foreach ($rates as $r) {
            $this->adjustedRates[$r->id] = $r->rate;
        }
    }

    // Save adjusted rates
    public function updateRate(int $id): void
    {
        if (isset($this->adjustedRates[$id])) {
            $rate = ExchangeRate::find($id);
            if ($rate) {
                $rate->update(['rate' => $this->adjustedRates[$id]]);
                session()->flash('rate_success', 'تم تحديث سعر الصرف بنجاح.');
            }
        }
    }

    // Sync all rates from API
    public function syncExchangeRates(): void
    {
        $rateService = app(ExchangeRateService::class);
        $rateService->syncAllRates();
        
        $this->loadRates();
        session()->flash('rate_success', 'تم جلب وتحديث أحدث أسعار الصرف من السوق العالمي (ExchangeRate-API) بنجاح.');
    }

    // Submit a manual transfer
    public function submitManualTransfer(): void
    {
        $this->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'destination' => 'required|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:10',
            'source_currency' => 'required|string|in:TRY,USD,EUR',
            'target_currency' => 'required|string|in:EGP,TRY',
        ]);

        $codeGen = app(SecretCodeGenerator::class);
        $secretCode = $codeGen->generate();
        
        $transferNumber = 'RD' . time();

        $transfer = Transfer::create([
            'transfer_number' => $transferNumber,
            'user_id' => auth()->id(),
            'sender_name' => $this->sender_name ?: null,
            'sender_phone' => $this->sender_phone ?: null,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'destination' => $this->destination,
            'address' => $this->address,
            'notes' => $this->notes,
            'amount' => $this->amount,
            'currency' => $this->source_currency,
            'target_currency' => $this->target_currency,
            'exchange_rate' => $this->exchange_rate,
            'received_amount' => $this->received_amount,
            'commission' => $this->commission,
            'net_amount' => $this->received_amount,
            'secret_code' => $secretCode,
            'status' => 'new',
            'created_by' => auth()->id(),
            'transferred_at' => Carbon::now(),
        ]);

        // Notify the creator (if it's an agent/admin)
        try {
            $transfer->creator->notify(new TransferStatusNotification($transfer, 'created'));
        } catch (\Exception $e) {
            Log::error("Failed to notify transfer creator: " . $e->getMessage());
        }

        // Notify all other admins
        $admins = \App\Models\User::where('role', 'admin')->where('id', '!=', auth()->id())->get();
        foreach ($admins as $admin) {
            $admin->notify(new TransferStatusNotification($transfer, 'created'));
        }

        // Show receipt modal
        $this->viewReceipt($transfer->id);

        // Reset fields
        $this->reset(['sender_name', 'sender_phone', 'recipient_name', 'recipient_phone', 'destination', 'address', 'notes', 'amount']);
        $this->calculateTotals();

        session()->flash('transfer_success', 'تم إنشاء الحوالة رقم ' . $transferNumber . ' بنجاح.');
    }

    // Reject customer request
    public function rejectRequest(int $id, string $notes): void
    {
        $transfer = Transfer::findOrFail($id);
        $transfer->update([
            'status' => 'rejected',
            'admin_notes' => $notes ?: 'تم الرفض من قبل المسؤول.'
        ]);

        session()->flash('request_success', 'تم رفض الطلب بنجاح.');
    }

    // Pay / Pay-out transfer (صرف الحوالة)
    public function payTransfer(int $id): void
    {
        $transfer = Transfer::findOrFail($id);
        if ($transfer->status !== 'new' && $transfer->status !== 'pending') {
            return;
        }

        // If it's a customer request (pending), lock in the financial data before paying out
        if ($transfer->status === 'pending') {
            $rateService = app(ExchangeRateService::class);
            $commissionService = app(CommissionCalculator::class);
            
            $rate = $rateService->getRate($transfer->currency, 'EGP');
            $commission = $commissionService->calculate((float) $transfer->amount);
            $received = $transfer->amount * $rate;
            
            $codeGen = app(SecretCodeGenerator::class);
            
            $transfer->update([
                'target_currency' => 'EGP',
                'exchange_rate' => $rate,
                'received_amount' => $received,
                'commission' => $commission,
                'net_amount' => $received,
                'secret_code' => $transfer->secret_code ?? $codeGen->generate(),
                'admin_notes' => 'تم التسليم مباشرة للعميل',
            ]);
        }

        $transfer->update([
            'status' => 'received',
            'paid_by' => auth()->id(),
            'delivered_at' => Carbon::now(),
        ]);

        // Notify client and admin
        try {
            if ($transfer->user_id) {
                $transfer->user->notify(new TransferStatusNotification($transfer, 'paid'));
            }
            // Notify the admin who processed the payment
            if (auth()->check()) {
                auth()->user()->notify(new TransferStatusNotification($transfer, 'paid'));
            }
        } catch (\Exception $e) {
            Log::error("Failed to notify on transfer pay: " . $e->getMessage());
        }

        $this->dispatch('transfer-paid');
        session()->flash('ledger_success', 'تم وصل الاستلام وتسليم الحوالة بنجاح.');
        
        // Show receipt
        $this->viewReceipt($transfer->id);
    }

    #[On('open-transfer-receipt')]
    public function handleOpenTransferReceipt($id)
    {
        $this->activeTab = 'ledger';
        $this->viewReceipt((int) $id);
    }

    // View Receipt in new tab
    public function viewReceipt(int $id): void
    {
        $this->selectedTransfer = Transfer::findOrFail($id);
        $receiptService = app(ReceiptService::class);
        $url = $receiptService->generatePdf($this->selectedTransfer);
        
        $this->js("window.open('{$url}', '_blank');");
    }

    // Logout
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    // Commissions
    public function toggleAutomatedCommissions(): void
    {
        $this->enableAutomatedCommissions = !$this->enableAutomatedCommissions;
        \App\Models\Setting::updateOrCreate(
            ['key' => 'enable_automated_commissions'],
            ['value' => $this->enableAutomatedCommissions ? 'true' : 'false']
        );
        $this->calculateTotals();
        session()->flash('commission_success', 'تم تحديث حالة العمولات الآلية بنجاح.');
    }

    public function saveDefaultCommission(): void
    {
        $this->validate(['defaultCommission' => 'required|numeric|min:0']);
        \App\Models\Setting::updateOrCreate(
            ['key' => 'default_commission_percentage'],
            ['value' => (string) $this->defaultCommission]
        );
        session()->flash('commission_success', 'تم حفظ النسبة الافتراضية للعمولات بنجاح.');
    }

    public function saveTier(): void
    {
        $this->validate([
            'tierMinAmount' => 'required|numeric|min:0',
            'tierMaxAmount' => 'required|numeric|gt:tierMinAmount',
            'tierCommissionType' => 'required|in:fixed,percentage',
            'tierCommissionValue' => 'required|numeric|min:0',
        ]);

        CommissionTier::create([
            'min_amount' => $this->tierMinAmount,
            'max_amount' => $this->tierMaxAmount,
            'commission_type' => $this->tierCommissionType,
            'commission_value' => $this->tierCommissionValue,
            'status' => 'active',
        ]);

        $this->reset(['tierMinAmount', 'tierMaxAmount', 'tierCommissionType', 'tierCommissionValue']);
        session()->flash('commission_success', 'تم إضافة شريحة العمولة بنجاح.');
    }

    public function deleteTier(int $id): void
    {
        CommissionTier::findOrFail($id)->delete();
        session()->flash('commission_success', 'تم حذف الشريحة بنجاح.');
    }

    // Render component
    public function render()
    {
        // Calculate ledger stats (for cards)
        $totalTrySent = Transfer::where('currency', 'TRY')->where('status', 'received')->sum('amount');
        $totalUsdSent = Transfer::where('currency', 'USD')->where('status', 'received')->sum('amount');
        $totalEurSent = Transfer::where('currency', 'EUR')->where('status', 'received')->sum('amount');
        
        // Equiv EGP (مقوم) - all paid transfers received amount in EGP
        $totalEgpPaid = Transfer::where('target_currency', 'EGP')->where('status', 'received')->sum('received_amount');

        // Incoming pending requests
        $incomingRequests = Transfer::with('user')->where('status', 'pending')->latest()->get();

        // Ledger list with filters
        $ledgerQuery = Transfer::with(['creator']);

        if (!empty($this->searchQuery)) {
            $q = '%' . $this->searchQuery . '%';
            $ledgerQuery->where(function ($sub) use ($q) {
                $sub->where('transfer_number', 'like', $q)
                    ->orWhere('sender_name', 'like', $q)
                    ->orWhere('recipient_name', 'like', $q)
                    ->orWhere('secret_code', 'like', $q);
            });
        }

        if ($this->ledgerStatusFilter !== 'all') {
            $ledgerQuery->where('status', $this->ledgerStatusFilter);
        }

        $transfers = $ledgerQuery->latest()->paginate(10);

        // Exchange Rates
        $exchangeRates = ExchangeRate::all();

        // Commissions
        $commissionTiers = CommissionTier::orderBy('min_amount')->get();

        return view('livewire.admin.admin-dashboard', compact(
            'totalTrySent',
            'totalUsdSent',
            'totalEurSent',
            'totalEgpPaid',
            'incomingRequests',
            'transfers',
            'exchangeRates',
            'commissionTiers'
        ));
    }
}
