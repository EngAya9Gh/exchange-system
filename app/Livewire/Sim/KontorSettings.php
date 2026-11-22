<?php

namespace App\Livewire\Sim;

use App\Livewire\Sim\Concerns\ControlsAutoSend;
use App\Models\ActivityLog;
use App\Services\KontorApiService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * «ربط الموردين»: the SIM suppliers (kontor86, telekomtl, same ZNET API).
 *  - Overview: which supplier is tried first (primary), each supplier's balance, linked packages and
 *    emergency switch (pause / resume automatic loading).
 *  - Credentials of the selected supplier: save from the UI and test the connection without a terminal.
 */
class KontorSettings extends Component
{
    use ControlsAutoSend;

    /** Supplier whose credentials are shown in the form */
    public string $supplier = KontorApiService::SUPPLIER_KONTOR86;

    public string $dealerCode = '';
    public string $password = '';

    /** Result of the last "test connection" click */
    public ?array $test = null;

    public function mount(): void
    {
        $this->authorizeManage();
        $this->loadCredentials();
    }

    protected function authorizeManage(): void
    {
        abort_unless((bool) auth()->user()?->can('manage_sims'), 403);
    }

    protected function api(): KontorApiService
    {
        return KontorApiService::for($this->supplier);
    }

    protected function loadCredentials(): void
    {
        $api = $this->api();
        $this->dealerCode = (string) $api->dealerCode();
        $this->password = (string) $api->password();
        $this->test = null;
        $this->resetValidation();
    }

    public function selectSupplier(string $supplier): void
    {
        $this->authorizeManage();
        if (!in_array($supplier, KontorApiService::SUPPLIERS, true)) {
            return;
        }

        $this->supplier = $supplier;
        $this->loadCredentials();
    }

    /** The supplier every order is tried at first (the admin picks the cheaper one). */
    public function makePrimary(string $supplier): void
    {
        $this->authorizeManage();
        abort_unless(in_array($supplier, KontorApiService::SUPPLIERS, true), 404);

        KontorApiService::setPrimarySupplier($supplier);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'sim_primary_supplier',
            'description' => "المورد الأساسي للباقات: {$supplier}",
            'ip_address' => request()->ip(),
        ]);

        session()->flash('primary_saved', __('messages.sim_supplier_primary_saved', ['supplier' => $supplier]));
    }

    public function save(): void
    {
        $this->authorizeManage();

        $this->validate([
            'dealerCode' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:100'],
        ], [
            'dealerCode.required' => __('messages.kontor_dealer_code_required'),
            'password.required' => __('messages.kontor_password_required'),
        ]);

        $this->api()->saveCredentials($this->dealerCode, $this->password);
        $this->dealerCode = trim($this->dealerCode);

        session()->flash('kontor_saved', __('messages.kontor_saved'));

        $this->testConnection();
    }

    public function testConnection(): void
    {
        $this->authorizeManage();

        $api = $this->api();
        Cache::forget($api->balanceCacheKey());

        if (!$api->isConfigured()) {
            $this->test = ['ok' => false, 'balance' => null, 'error' => 'NOT_CONFIGURED', 'ip' => $api->serverPublicIp(), 'at' => now()->format('H:i:s')];
            return;
        }

        $result = $api->balance();
        if ($result['ok']) {
            Cache::put($api->balanceCacheKey(), $result, 60);
        }

        $this->test = $result + ['ip' => $api->serverPublicIp(), 'at' => now()->format('H:i:s')];
    }

    /** Plain-language explanation of the supplier's error code */
    public function explanation(string $error): string
    {
        return match ($error) {
            'API_BAGLANTI_HATASI', 'EMPTY_RESPONSE' => __('messages.kontor_err_ip_or_permission'),
            'NO_RESPONSE' => __('messages.kontor_err_no_response'),
            'NOT_CONFIGURED' => __('messages.kontor_err_not_configured'),
            default => __('messages.kontor_err_other', ['error' => $error]),
        };
    }

    public function render()
    {
        $suppliers = KontorApiService::overview();
        foreach ($suppliers as $name => $state) {
            $suppliers[$name]['balance'] = $state['configured'] ? KontorApiService::for($name)->cachedBalance() : null;
        }

        return view('livewire.sim.kontor-settings', ['suppliers' => $suppliers]);
    }
}
