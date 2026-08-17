<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Setting;
use App\Models\CommissionTier;

class TransferCommissions extends Component
{
    public bool $enableAutomatedCommissions = true;
    public float $defaultCommission = 2.0;
    
    public $agentCommission = 0.5;
    public $customerCommission = 1.0;

    public $tierMinAmount = '';
    public $tierMaxAmount = '';
    public $tierTargetRole = 'all'; // all, agent, customer
    public $tierCommissionType = 'fixed';
    public $tierCommissionValue = '';

    public function mount()
    {
        $enableSetting = Setting::where('key', 'enable_automated_commissions')->first();
        $this->enableAutomatedCommissions = $enableSetting ? filter_var($enableSetting->value, FILTER_VALIDATE_BOOLEAN) : true;

        $defaultSetting = Setting::where('key', 'default_commission_percentage')->first();
        $this->defaultCommission = $defaultSetting ? (float) $defaultSetting->value : 2.0;
        
        $agentSetting = Setting::where('key', 'agent_commission_percentage')->first();
        $this->agentCommission = $agentSetting ? (float) $agentSetting->value : 0.5;

        $customerSetting = Setting::where('key', 'customer_commission_percentage')->first();
        $this->customerCommission = $customerSetting ? (float) $customerSetting->value : 1.0;
    }

    public function toggleAutomatedCommissions(): void
    {
        $this->enableAutomatedCommissions = !$this->enableAutomatedCommissions;
        Setting::updateOrCreate(
            ['key' => 'enable_automated_commissions'],
            ['value' => $this->enableAutomatedCommissions ? 'true' : 'false']
        );
        session()->flash('commission_success', 'تم تحديث حالة العمولات الآلية بنجاح.');
    }

    public function saveDefaultCommission(): void
    {
        $this->validate(['defaultCommission' => 'required|numeric|min:0']);
        Setting::updateOrCreate(
            ['key' => 'default_commission_percentage'],
            ['value' => (string) $this->defaultCommission]
        );
        session()->flash('commission_success', 'تم حفظ النسبة الافتراضية للعمولات بنجاح.');
    }

    public function saveRoleCommissions(): void
    {
        $this->validate([
            'agentCommission' => 'required|numeric|min:0',
            'customerCommission' => 'required|numeric|min:0',
        ]);

        Setting::updateOrCreate(
            ['key' => 'agent_commission_percentage'],
            ['value' => (string) $this->agentCommission]
        );

        Setting::updateOrCreate(
            ['key' => 'customer_commission_percentage'],
            ['value' => (string) $this->customerCommission]
        );

        session()->flash('commission_success', 'تم حفظ نسب العمولات الخاصة بالصلاحيات بنجاح.');
    }

    public function saveTier(): void
    {
        $this->validate([
            'tierMinAmount' => 'required|numeric|min:0',
            'tierMaxAmount' => 'required|numeric|gt:tierMinAmount',
            'tierTargetRole' => 'required|in:all,agent,customer',
            'tierCommissionType' => 'required|in:fixed,percentage',
            'tierCommissionValue' => 'required|numeric|min:0',
        ]);

        CommissionTier::create([
            'min_amount' => $this->tierMinAmount,
            'max_amount' => $this->tierMaxAmount,
            'target_role' => $this->tierTargetRole,
            'commission_type' => $this->tierCommissionType,
            'commission_value' => $this->tierCommissionValue,
            'status' => 'active',
        ]);

        $this->reset(['tierMinAmount', 'tierMaxAmount', 'tierTargetRole', 'tierCommissionType', 'tierCommissionValue']);
        session()->flash('commission_success', 'تم إضافة شريحة العمولة بنجاح.');
    }

    public function deleteTier(int $id): void
    {
        CommissionTier::findOrFail($id)->delete();
        session()->flash('commission_success', 'تم حذف الشريحة بنجاح.');
    }

    public function render()
    {
        $tiers = CommissionTier::orderBy('min_amount')->get();
        return view('livewire.admin.transfer-commissions', compact('tiers'));
    }
}
