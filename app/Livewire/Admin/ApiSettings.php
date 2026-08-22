<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Setting;

class ApiSettings extends Component
{
    public $apiUrl;
    public $dealerCode;
    public $username;
    public $password;

    public function mount()
    {
        $this->apiUrl = Setting::where('key', 'FATURA_API_URL')->value('value') ?? env('FATURA_API_URL', 'http://bayi.bayiwebpanel.tech/ClientWebService');
        $this->dealerCode = Setting::where('key', 'FATURA_API_DEALER_CODE')->value('value') ?? env('FATURA_API_DEALER_CODE', '');
        $this->username = Setting::where('key', 'FATURA_API_USERNAME')->value('value') ?? env('FATURA_API_USERNAME', '');
        
        // You may choose not to display the password, or display it securely.
        $this->password = Setting::where('key', 'FATURA_API_PASSWORD')->value('value') ?? env('FATURA_API_PASSWORD', '');
    }

    public function saveSettings()
    {
        $this->validate([
            'apiUrl' => 'required|url',
            'dealerCode' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        Setting::updateOrCreate(['key' => 'FATURA_API_URL'], ['value' => $this->apiUrl]);
        Setting::updateOrCreate(['key' => 'FATURA_API_DEALER_CODE'], ['value' => $this->dealerCode]);
        Setting::updateOrCreate(['key' => 'FATURA_API_USERNAME'], ['value' => $this->username]);
        Setting::updateOrCreate(['key' => 'FATURA_API_PASSWORD'], ['value' => $this->password]);

        session()->flash('success', 'تم حفظ إعدادات الـ API بنجاح.');
    }

    public function render()
    {
        return view('livewire.admin.api-settings')->layout('layouts.app');
    }
}
