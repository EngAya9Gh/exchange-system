<?php

namespace App\Livewire\Sim;

use App\Models\Setting;
use App\Services\KontorApiService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * "ربط kontor86": save the dealer code + API password from the UI and test the
 * connection (balance call + this server's public IP) without using a terminal.
 */
class KontorSettings extends Component
{
    public string $dealerCode = '';
    public string $password = '';

    /** Result of the last "test connection" click */
    public ?array $test = null;

    public function mount(): void
    {
        $this->authorizeManage();

        $api = app(KontorApiService::class);
        $this->dealerCode = (string) $api->dealerCode();
        $this->password = (string) $api->password();
    }

    protected function authorizeManage(): void
    {
        abort_unless((bool) auth()->user()?->can('manage_sims'), 403);
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

        Setting::updateOrCreate(['key' => KontorApiService::SETTING_DEALER_CODE], ['value' => trim($this->dealerCode)]);
        Setting::updateOrCreate(['key' => KontorApiService::SETTING_PASSWORD], ['value' => trim($this->password)]);
        Cache::forget(KontorApiService::BALANCE_CACHE_KEY);

        session()->flash('kontor_saved', __('messages.kontor_saved'));

        $this->testConnection();
    }

    public function testConnection(): void
    {
        $this->authorizeManage();

        $api = app(KontorApiService::class);
        Cache::forget(KontorApiService::BALANCE_CACHE_KEY);

        if (!$api->isConfigured()) {
            $this->test = ['ok' => false, 'balance' => null, 'error' => 'NOT_CONFIGURED', 'ip' => $api->serverPublicIp(), 'at' => now()->format('H:i:s')];
            return;
        }

        $result = $api->balance();
        if ($result['ok']) {
            Cache::put(KontorApiService::BALANCE_CACHE_KEY, $result, 60);
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
        return view('livewire.sim.kontor-settings');
    }
}
