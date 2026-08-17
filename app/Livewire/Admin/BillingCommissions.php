<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Setting;

class BillingCommissions extends Component
{
    public bool $enableAutomatedCommissions = true;
    public float $defaultCommission = 2.0;
    
    public $agentCommission = 0.5;
    public $customerCommission = 1.0;

    public function mount()
    {
        $enableSetting = Setting::where('key', 'billing_enable_automated_commissions')->first();
        $this->enableAutomatedCommissions = $enableSetting ? filter_var($enableSetting->value, FILTER_VALIDATE_BOOLEAN) : true;

        $defaultSetting = Setting::where('key', 'billing_default_commission_percentage')->first();
        $this->defaultCommission = $defaultSetting ? (float) $defaultSetting->value : 2.0;
        
        $agentSetting = Setting::where('key', 'billing_agent_commission_percentage')->first();
        $this->agentCommission = $agentSetting ? (float) $agentSetting->value : 0.5;

        $customerSetting = Setting::where('key', 'billing_customer_commission_percentage')->first();
        $this->customerCommission = $customerSetting ? (float) $customerSetting->value : 1.0;
    }

    public function toggleAutomatedCommissions(): void
    {
        $this->enableAutomatedCommissions = !$this->enableAutomatedCommissions;
        Setting::updateOrCreate(
            ['key' => 'billing_enable_automated_commissions'],
            ['value' => $this->enableAutomatedCommissions ? 'true' : 'false']
        );
        session()->flash('commission_success', 'تم تحديث حالة العمولات الآلية للفواتير بنجاح.');
    }

    public function saveDefaultCommission(): void
    {
        $this->validate(['defaultCommission' => 'required|numeric|min:0']);
        Setting::updateOrCreate(
            ['key' => 'billing_default_commission_percentage'],
            ['value' => (string) $this->defaultCommission]
        );
        session()->flash('commission_success', 'تم حفظ النسبة الافتراضية لعمولات الفواتير بنجاح.');
    }

    public function saveRoleCommissions(): void
    {
        $this->validate([
            'agentCommission' => 'required|numeric|min:0',
            'customerCommission' => 'required|numeric|min:0',
        ]);

        Setting::updateOrCreate(
            ['key' => 'billing_agent_commission_percentage'],
            ['value' => (string) $this->agentCommission]
        );

        Setting::updateOrCreate(
            ['key' => 'billing_customer_commission_percentage'],
            ['value' => (string) $this->customerCommission]
        );

        session()->flash('commission_success', 'تم حفظ نسب عمولات الفواتير الخاصة بالصلاحيات بنجاح.');
    }

    public function render()
    {
        return view('livewire.admin.billing-commissions');
    }
}
