<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class BalanceManagement extends Component
{
    use WithPagination;

    public $searchQuery = '';

    // Modal States
    public $manageUserId = null;
    public $manageUserName = '';
    public $manageUserBalance = 0;

    // Deposit Modal
    public $showDepositModal = false;
    public $depositAmount = '';
    public $depositNote = '';

    // Summary Modal
    public $showSummaryModal = false;
    public $summaryData = [];
    public $lastSummaryData = [];
    public $openedFromStatementUserId = null;

    // Statement Modal
    public $showStatementModal = false;
    public $statementTransactions = [];

    // Settings Modal
    public $showSettingsModal = false;
    public $editHasUnlimited = false;
    public $editBalanceLimit = 0;
    public $absoluteBalance = 0; // In case they want to overwrite completely

    public function updatingSearchQuery()
    {
        $this->resetPage();
    }

    public function openDepositModal($id)
    {
        $user = User::findOrFail($id);
        $this->manageUserId = $user->id;
        $this->manageUserName = $user->name;
        $this->manageUserBalance = $user->balance;
        
        $this->depositAmount = '';
        $this->depositNote = '';
        $this->showDepositModal = true;
        $this->showSettingsModal = false;
    }

    public function openSettingsModal($id)
    {
        $user = User::findOrFail($id);
        $this->manageUserId = $user->id;
        $this->manageUserName = $user->name;
        $this->manageUserBalance = $user->balance;
        
        $this->absoluteBalance = $user->balance;
        $this->editHasUnlimited = $user->has_unlimited_balance;
        $this->editBalanceLimit = $user->balance_limit;
        
        $this->showSettingsModal = true;
        $this->showDepositModal = false;
    }

    // Payment Modal
    public $showPaymentModal = false;
    public $paymentAmount = '';
    public $paymentMethod = 'cash';
    public $paymentNotes = '';

    public function openPaymentModal($id)
    {
        $user = User::findOrFail($id);
        $this->manageUserId = $user->id;
        $this->manageUserName = $user->name;
        $this->manageUserBalance = $user->balance;
        
        $this->paymentAmount = '';
        $this->paymentMethod = 'cash';
        $this->paymentNotes = '';
        $this->showPaymentModal = true;
        $this->showDepositModal = false;
        $this->showSettingsModal = false;
    }

    public function closeModals()
    {
        $this->showDepositModal = false;
        $this->showSettingsModal = false;
        $this->showPaymentModal = false;
        $this->showStatementModal = false;
        $this->showSummaryModal = false;
        $this->manageUserId = null;
        $this->summaryData = [];
    }

    public function closeSummaryModal()
    {
        $this->showSummaryModal = false;
        $this->summaryData = [];
        $this->manageUserId = null;
        $this->depositAmount = '';
        $this->depositNote = '';

        if ($this->openedFromStatementUserId) {
            $userId = $this->openedFromStatementUserId;
            $this->openedFromStatementUserId = null;
            $this->openStatementModal($userId);
        }
    }

    public function reopenLastSummary()
    {
        if (!empty($this->lastSummaryData)) {
            $this->summaryData = $this->lastSummaryData;
            $this->showSummaryModal = true;
        }
    }

    public function dismissLastSummaryBanner()
    {
        $this->lastSummaryData = [];
    }

    public function openTransactionSummary($transactionId)
    {
        $tx = \App\Models\BalanceTransaction::with('user', 'admin')->findOrFail($transactionId);

        $typeText = match($tx->type) {
            'deposit' => 'إيداع رصيد (إضافة +)',
            'withdrawal' => 'سحب رصيد (خصم -)',
            'payment' => 'سداد دفعة (+)',
            default => 'تعديل رصيد',
        };

        $this->openedFromStatementUserId = $tx->user_id;

        $this->summaryData = [
            'type' => $tx->type,
            'type_text' => $typeText,
            'user_name' => $tx->user?->name ?? 'غير محدد',
            'user_phone' => $tx->user?->phone,
            'amount' => (float)$tx->amount,
            'balance_before' => (float)$tx->balance_before,
            'balance_after' => (float)$tx->balance_after,
            'date' => $tx->created_at->format('Y-m-d h:i A'),
            'transaction_id' => $tx->id,
            'notes' => $tx->notes,
        ];
        $this->lastSummaryData = $this->summaryData;

        $this->showStatementModal = false;
        $this->showDepositModal = false;
        $this->showPaymentModal = false;
        $this->showSettingsModal = false;
        $this->showSummaryModal = true;
    }

    public function openStatementModal($id)
    {
        $user = User::findOrFail($id);
        $this->manageUserId = $user->id;
        $this->manageUserName = $user->name;
        $this->manageUserBalance = $user->balance;
        
        $this->statementTransactions = \App\Models\BalanceTransaction::where('user_id', $user->id)
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $this->showStatementModal = true;
        $this->showDepositModal = false;
        $this->showSettingsModal = false;
        $this->showPaymentModal = false;
    }

    public function confirmDeposit()
    {
        $this->validate([
            'depositAmount' => 'required|numeric|min:0.01',
        ], [
            'depositAmount.required' => 'الرجاء إدخال المبلغ.',
            'depositAmount.min' => 'المبلغ يجب أن يكون أكبر من الصفر.',
        ]);

        $user = User::findOrFail($this->manageUserId);
        $balanceBefore = $user->balance;
        $user->balance += (float)$this->depositAmount;
        $user->save();

        $transaction = \App\Models\BalanceTransaction::create([
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'type' => 'deposit',
            'amount' => (float)$this->depositAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $user->balance,
            'notes' => 'إيداع يدوي',
        ]);

        $this->summaryData = [
            'type' => 'deposit',
            'type_text' => 'إيداع رصيد (إضافة +)',
            'user_name' => $user->name,
            'user_phone' => $user->phone,
            'amount' => (float)$this->depositAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $user->balance,
            'date' => now()->format('Y-m-d h:i A'),
            'transaction_id' => $transaction->id,
        ];
        $this->lastSummaryData = $this->summaryData;

        session()->flash('success', "تم إضافة الرصيد بنجاح لـ {$this->manageUserName}.");
        $this->showDepositModal = false;
        $this->showSummaryModal = true;
    }

    public function confirmWithdrawal()
    {
        $this->validate([
            'depositAmount' => 'required|numeric|min:0.01',
        ], [
            'depositAmount.required' => 'الرجاء إدخال المبلغ.',
            'depositAmount.min' => 'المبلغ يجب أن يكون أكبر من الصفر.',
        ]);

        $user = User::findOrFail($this->manageUserId);
        $balanceBefore = $user->balance;
        $user->balance -= (float)$this->depositAmount;
        $user->save();

        $transaction = \App\Models\BalanceTransaction::create([
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'type' => 'withdrawal',
            'amount' => (float)$this->depositAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $user->balance,
            'notes' => 'سحب يدوي',
        ]);

        $this->summaryData = [
            'type' => 'withdrawal',
            'type_text' => 'سحب رصيد (خصم -)',
            'user_name' => $user->name,
            'user_phone' => $user->phone,
            'amount' => (float)$this->depositAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $user->balance,
            'date' => now()->format('Y-m-d h:i A'),
            'transaction_id' => $transaction->id,
        ];
        $this->lastSummaryData = $this->summaryData;

        session()->flash('success', "تم خصم الرصيد بنجاح من {$this->manageUserName}.");
        $this->showDepositModal = false;
        $this->showSummaryModal = true;
    }

    public function confirmPayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|string',
            'paymentNotes' => 'nullable|string|max:500',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                $user = User::findOrFail($this->manageUserId);
                $balanceBefore = $user->balance;
                $user->balance += (float)$this->paymentAmount;
                $user->save();

                // Register in Payment Table
                \App\Models\Payment::create([
                    'user_id' => $user->id,
                    'admin_id' => auth()->id(),
                    'amount' => $this->paymentAmount,
                    'payment_method' => $this->paymentMethod,
                    'notes' => $this->paymentNotes
                ]);

                // Register in BalanceTransaction Table
                \App\Models\BalanceTransaction::create([
                    'user_id' => $user->id,
                    'admin_id' => auth()->id(),
                    'type' => 'payment',
                    'amount' => (float)$this->paymentAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $user->balance,
                    'notes' => "تسجيل دفعة ({$this->paymentMethod}) - " . $this->paymentNotes,
                ]);

                // Send notification
                $user->notify(new \App\Notifications\PaymentReceivedNotification($this->paymentAmount, $this->paymentMethod, $this->paymentNotes));
            });

            session()->flash('success', "تم استلام وتسجيل الدفعة بنجاح لـ {$this->manageUserName}.");
            $this->closeModals();
            
        } catch (\Exception $e) {
            session()->flash('error', 'حدث خطأ أثناء معالجة الدفعة: ' . $e->getMessage());
        }
    }

    public function confirmSettings()
    {
        $this->validate([
            'absoluteBalance' => 'required|numeric',
            'editHasUnlimited' => 'required|boolean',
            'editBalanceLimit' => 'required_if:editHasUnlimited,false|numeric|min:0',
        ]);

        $user = User::findOrFail($this->manageUserId);
        $user->balance = $this->absoluteBalance;
        $user->has_unlimited_balance = $this->editHasUnlimited;
        $user->balance_limit = $this->editBalanceLimit;
        $user->save();

        session()->flash('success', "تم تحديث إعدادات الحساب بنجاح لـ {$this->manageUserName}.");
        $this->closeModals();
    }

    public function cancelEdit()
    {
        $this->closeModals();
    }

    public function render()
    {
        $users = User::whereDoesntHave('roles', function($q) {
                $q->where('name', 'Super Admin');
            })
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('username', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('phone', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('email', 'like', '%' . $this->searchQuery . '%');
            })
            ->paginate(10);

        return view('livewire.admin.balance-management', compact('users'));
    }
}
