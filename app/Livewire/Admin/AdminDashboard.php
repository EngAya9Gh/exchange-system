<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\CommissionTier;
use App\Models\ExchangeRate;
use App\Models\Transfer;
use App\Models\TransferRequest;
use App\Services\CommissionCalculator;
use App\Services\ExchangeRateService;
use App\Services\SecretCodeGenerator;
use App\Services\ReceiptService;
use App\Notifications\TransferStatusNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class AdminDashboard extends Component
{
    use WithPagination;

    // Tabs state
    public string $activeTab = 'dashboard'; // dashboard | new_transfer | ledger | rates | requests

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

    // Search and filter in ledger
    public string $searchQuery = '';
    public string $ledgerStatusFilter = 'all';

    // Rate adjustment state
    public array $adjustedRates = [];

    // Receipt modal state
    public ?Transfer $selectedTransfer = null;
    public bool $showReceiptModal = false;
    public string $receiptPdfUrl = '';

    public function mount(): void
    {
        $this->calculateTotals();
        $this->loadRates();
    }

    public function updatedAmount(): void
    {
        $this->calculateTotals();
    }

    public function updatedSourceCurrency(): void
    {
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        if (empty($this->amount) || !is_numeric($this->amount) || $this->amount <= 0) {
            $this->exchange_rate = 0.0;
            $this->commission = 0.0;
            $this->received_amount = 0.0;
            $this->total_to_pay = 0.0;
            return;
        }

        $rateService = app(ExchangeRateService::class);
        $commissionService = app(CommissionCalculator::class);

        $this->exchange_rate = $rateService->getRate($this->source_currency, $this->target_currency);
        $this->commission = $commissionService->calculate((float)$this->amount);
        
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
            'sender_name' => $this->sender_name ?: null,
            'sender_phone' => $this->sender_phone ?: null,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'destination' => $this->destination,
            'address' => $this->address,
            'notes' => $this->notes,
            'source_amount' => $this->amount,
            'source_currency' => $this->source_currency,
            'target_currency' => $this->target_currency,
            'exchange_rate' => $this->exchange_rate,
            'received_amount' => $this->received_amount,
            'commission' => $this->commission,
            'net_amount' => $this->received_amount,
            'secret_code' => $secretCode,
            'status' => 'pending',
            'created_by' => auth()->id(),
            'transferred_at' => Carbon::now(),
        ]);

        // Trigger WhatsApp Notification (Mock or Real)
        try {
            $transfer->creator->notify(new TransferStatusNotification($transfer, 'created'));
        } catch (\Exception $e) {
            Log::error("Failed to notify transfer creator: " . $e->getMessage());
        }

        // Show receipt modal
        $this->viewReceipt($transfer->id);

        // Reset fields
        $this->reset(['sender_name', 'sender_phone', 'recipient_name', 'recipient_phone', 'destination', 'address', 'notes', 'amount']);
        $this->calculateTotals();

        session()->flash('transfer_success', 'تم إنشاء الحوالة رقم ' . $transferNumber . ' بنجاح.');
    }

    // Approve customer transfer request
    public function approveRequest(int $id): void
    {
        $request = TransferRequest::findOrFail($id);
        $rateService = app(ExchangeRateService::class);
        $commissionService = app(CommissionCalculator::class);

        $rate = $rateService->getRate($request->currency, 'EGP');
        $commission = $commissionService->calculate($request->amount);
        $received = $request->amount * $rate;

        $codeGen = app(SecretCodeGenerator::class);
        $secretCode = $codeGen->generate();
        $transferNumber = 'RD' . time();

        $transfer = Transfer::create([
            'transfer_number' => $transferNumber,
            'request_id' => $request->id,
            'sender_name' => $request->sender_name,
            'sender_phone' => $request->sender_phone,
            'recipient_name' => $request->recipient_name,
            'recipient_phone' => $request->recipient_phone,
            'destination' => $request->destination ?? 'جميع المحافظات - فودافون مباشر',
            'address' => $request->address,
            'notes' => $request->notes,
            'source_amount' => $request->amount,
            'source_currency' => $request->currency,
            'target_currency' => 'EGP',
            'exchange_rate' => $rate,
            'received_amount' => $received,
            'commission' => $commission,
            'net_amount' => $received,
            'secret_code' => $secretCode,
            'status' => 'pending',
            'created_by' => auth()->id(),
            'transferred_at' => Carbon::now(),
        ]);

        $request->update(['status' => 'approved', 'admin_notes' => 'تم القبول وإنشاء حوالة برقم: ' . $transferNumber]);

        // Notify customer
        try {
            $request->user->notify(new TransferStatusNotification($transfer, 'created'));
        } catch (\Exception $e) {
            Log::error("Failed to notify user on request approval: " . $e->getMessage());
        }

        session()->flash('request_success', 'تم قبول الطلب وتحويله لحوالة بنجاح.');
    }

    // Reject customer request
    public function rejectRequest(int $id, string $notes): void
    {
        $request = TransferRequest::findOrFail($id);
        $request->update([
            'status' => 'rejected',
            'admin_notes' => $notes ?: 'تم الرفض من قبل المسؤول.'
        ]);

        session()->flash('request_success', 'تم رفض الطلب بنجاح.');
    }

    // Pay / Pay-out transfer (صرف الحوالة)
    public function payTransfer(int $id): void
    {
        $transfer = Transfer::findOrFail($id);
        if ($transfer->status !== 'pending') {
            return;
        }

        $transfer->update([
            'status' => 'paid',
            'paid_by' => auth()->id(),
            'delivered_at' => Carbon::now(),
        ]);

        // Notify client
        try {
            $transfer->creator->notify(new TransferStatusNotification($transfer, 'paid'));
        } catch (\Exception $e) {
            Log::error("Failed to notify user on transfer pay: " . $e->getMessage());
        }

        session()->flash('ledger_success', 'تم صرف الحوالة بنجاح وتحديث الحالة.');
    }

    // View Receipt Modal
    public function viewReceipt(int $id): void
    {
        $this->selectedTransfer = Transfer::findOrFail($id);
        $receiptService = app(ReceiptService::class);
        $this->receiptPdfUrl = $receiptService->generatePdf($this->selectedTransfer);
        $this->showReceiptModal = true;
    }

    // Render component
    public function render()
    {
        // Calculate ledger stats (for cards)
        $totalTrySent = Transfer::where('source_currency', 'TRY')->where('status', 'paid')->sum('source_amount');
        $totalUsdSent = Transfer::where('source_currency', 'USD')->where('status', 'paid')->sum('source_amount');
        $totalEurSent = Transfer::where('source_currency', 'EUR')->where('status', 'paid')->sum('source_amount');
        
        // Equiv EGP (مقوم) - all paid transfers received amount in EGP
        $totalEgpPaid = Transfer::where('target_currency', 'EGP')->where('status', 'paid')->sum('received_amount');

        // Incoming pending requests
        $incomingRequests = TransferRequest::with('user')->where('status', 'pending')->latest()->get();

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

        return view('livewire.admin.admin-dashboard', compact(
            'totalTrySent',
            'totalUsdSent',
            'totalEurSent',
            'totalEgpPaid',
            'incomingRequests',
            'transfers',
            'exchangeRates'
        ));
    }
}
