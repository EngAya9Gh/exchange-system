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
        $this->manageUserId = null;
    }

    public function confirmDeposit()
    {
        $this->validate([
            'depositAmount' => 'required|numeric|not_in:0',
        ], [
            'depositAmount.required' => 'الرجاء إدخال المبلغ.',
            'depositAmount.not_in' => 'المبلغ لا يمكن أن يكون صفراً.',
        ]);

        $user = User::findOrFail($this->manageUserId);
        $user->balance += (float)$this->depositAmount;
        $user->save();

        $action = (float)$this->depositAmount > 0 ? 'إضافة' : 'خصم';
        session()->flash('success', "تم $action الرصيد بنجاح لـ {$this->manageUserName}.");
        $this->closeModals();
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
        $users = User::role('Customer')
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('phone', 'like', '%' . $this->searchQuery . '%');
            })
            ->paginate(10);

        return view('livewire.admin.balance-management', compact('users'));
    }
}
