<?php

namespace Tests\Feature;

use App\Livewire\Sim\Orders;
use App\Models\User;
use App\Services\KontorApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class KontorApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KontorApiService $api;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.kontor.base_url' => 'https://bayi.kontor86.com/servis',
            'services.kontor.dealer_code' => '5000000000',
            'services.kontor.password' => 'secret',
        ]);
        $this->api = app(KontorApiService::class);
    }

    public function test_balance_ok_and_request_format(): void
    {
        Http::fake(['bayi.kontor86.com/servis/bakiye_kontrol.php*' => Http::response('OK|12755.23')]);

        $this->assertSame(['ok' => true, 'balance' => 12755.23, 'error' => null], $this->api->balance());

        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://bayi.kontor86.com/servis/bakiye_kontrol.php?')
            && $r['kod'] === '5000000000' && $r['sifre'] === 'secret');
    }

    public function test_balance_errors(): void
    {
        Http::fakeSequence('bayi.kontor86.com/*')
            ->push('NOK|API_BAGLANTI_HATASI') // IP not registered / API not enabled (what we get today)
            ->push('')                        // documented: empty body for the same reasons
            ->push('', 500);

        $this->assertSame(['ok' => false, 'balance' => null, 'error' => 'API_BAGLANTI_HATASI'], $this->api->balance());
        $this->assertSame('EMPTY_RESPONSE', $this->api->balance()['error']);
        $this->assertSame('NO_RESPONSE', $this->api->balance()['error']);
    }

    public function test_send_top_up_parses_every_documented_answer(): void
    {
        Http::fakeSequence('bayi.kontor86.com/servis/tl_servis.php*')
            ->push('OK|1|Talebiniz İşleme Alınmıştır.|5.50')
            ->push('OK|3|Aynı Numaraya Aynı Miktarda 10 Dakika İçinde İşlem Gerçekleşti|0.00')
            ->push('OK|8|Bu İşlem Daha Önce Gönderildi|0.00')
            ->push('NOK|API_BAGLANTI_HATASI');

        $send = fn () => $this->api->sendTopUp('turkcell', '3gcep', '5', '5051234567', 'SIM2611081234');

        $this->assertSame(['ok' => true, 'status' => 1, 'message' => 'Talebiniz İşleme Alınmıştır.', 'cost' => 5.5], $send());
        $this->assertSame(['ok' => false, 'status' => 3, 'message' => 'Aynı Numaraya Aynı Miktarda 10 Dakika İçinde İşlem Gerçekleşti', 'cost' => 0.0], $send());
        $this->assertSame(8, $send()['status']);
        $this->assertSame(['ok' => false, 'status' => 3, 'message' => 'API_BAGLANTI_HATASI', 'cost' => null], $send());

        Http::assertSent(fn (Request $r) => $r['bayi_kodu'] === '5000000000' && $r['operator'] === 'turkcell'
            && $r['tip'] === '3gcep' && $r['kontor'] === '5' && $r['gsmno'] === '5051234567' && $r['tekilnumara'] === 'SIM2611081234');
    }

    public function test_send_timeout_is_never_reported_as_failed(): void
    {
        // We cannot know whether it was loaded → "check", so nobody refunds and resends blindly
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $result = $this->api->sendTopUp('turkcell', '3gcep', '5', '5051234567', 'SIM1');

        $this->assertFalse($result['ok']);
        $this->assertSame(KontorApiService::SEND_CHECK, $result['status']);
    }

    public function test_check_top_up_states(): void
    {
        Http::fakeSequence('bayi.kontor86.com/servis/tl_kontrol.php*')
            ->push('1:olumlu_islem:5.50')
            ->push('2:islemde:5.50')
            ->push('3:Numara hatalı')
            ->push('');

        $this->assertSame(['state' => 'success', 'cost' => 5.5, 'reason' => null], $this->api->checkTopUp('SIM1'));
        $this->assertSame(['state' => 'processing', 'cost' => 5.5, 'reason' => null], $this->api->checkTopUp('SIM1'));
        $this->assertSame(['state' => 'cancelled', 'cost' => null, 'reason' => 'Numara hatalı'], $this->api->checkTopUp('SIM1'));
        $this->assertSame('unknown', $this->api->checkTopUp('SIM1')['state']);
    }

    public function test_password_is_never_written_to_the_log(): void
    {
        $logFile = storage_path('logs/laravel.log');
        $before = is_file($logFile) ? filesize($logFile) : 0;
        Http::fake(['*' => Http::response('NOK|API_BAGLANTI_HATASI')]);
        config(['services.kontor.password' => 'Very$ecretPw']);

        $this->api->balance();

        $written = is_file($logFile) ? (string) file_get_contents($logFile, false, null, $before) : '';
        $this->assertStringNotContainsString('Very$ecretPw', $written);
    }

    protected function makeUsers(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Super Admin', 'web')->givePermissionTo('manage_sims');
        Role::findOrCreate('Customer', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $customer = User::factory()->create();
        $customer->assignRole('Customer');

        return [$admin, $customer];
    }

    public function test_settings_saved_from_the_screen_win_over_env(): void
    {
        $this->fakeKontor('OK|1.00');
        \App\Models\Setting::create(['key' => 'KONTOR_DEALER_CODE', 'value' => '5392065498']);
        \App\Models\Setting::create(['key' => 'KONTOR_API_PASSWORD', 'value' => 'from-ui']);

        $this->api->balance();

        Http::assertSent(fn (Request $r) => $r['kod'] === '5392065498' && $r['sifre'] === 'from-ui');
    }

    public function test_connection_screen_saves_and_tests_from_the_ui(): void
    {
        [$admin, $customer] = $this->makeUsers();
        config(['services.kontor.dealer_code' => null, 'services.kontor.password' => null]);

        // Only SIM managers can open it
        Livewire::actingAs($customer)->test(\App\Livewire\Sim\KontorSettings::class)->assertForbidden();

        // Not connected yet: explains why and shows this server's IP to send to the supplier
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake([
            'bayi.kontor86.com/*' => Http::response('NOK|API_BAGLANTI_HATASI'),
            'api.ipify.org*' => Http::response('76.13.149.149'),
        ]);

        Livewire::actingAs($admin)->test(\App\Livewire\Sim\KontorSettings::class)
            ->set('dealerCode', ' 5392065498 ')
            ->set('password', 'kapuz578')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee(__('messages.kontor_saved'))
            ->assertSee(__('messages.kontor_not_connected'))
            ->assertSee(__('messages.kontor_err_ip_or_permission'))
            ->assertSee('76.13.149.149');

        $this->assertSame('5392065498', \App\Models\Setting::where('key', 'KONTOR_DEALER_CODE')->value('value'));
        $this->assertSame('kapuz578', \App\Models\Setting::where('key', 'KONTOR_API_PASSWORD')->value('value'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'bakiye_kontrol.php') && $r['sifre'] === 'kapuz578');

        // Once the supplier registers the IP: green with the balance (fresh call, not the cache)
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake([
            'bayi.kontor86.com/*' => Http::response('OK|12755.23'),
            'api.ipify.org*' => Http::response('76.13.149.149'),
        ]);

        Livewire::actingAs($admin)->test(\App\Livewire\Sim\KontorSettings::class)
            ->assertSet('dealerCode', '5392065498') // loaded from the saved settings
            ->call('testConnection')
            ->assertSee(__('messages.kontor_connected'))
            ->assertSee('12,755.23 ₺');

        // Empty fields are rejected
        Livewire::actingAs($admin)->test(\App\Livewire\Sim\KontorSettings::class)
            ->set('password', '')
            ->call('save')
            ->assertHasErrors(['password' => 'required']);
    }

    public function test_connection_screen_is_reachable_from_the_admin_sidebar(): void
    {
        [$admin] = $this->makeUsers();
        config(['services.kontor.password' => null]); // no balance call while rendering the dashboard

        Livewire::withQueryParams(['activeTab' => 'sim_kontor'])
            ->actingAs($admin)
            ->test(\App\Livewire\Admin\AdminDashboard::class)
            ->assertSee(__('messages.kontor_menu'))
            ->assertSeeHtml('sim.kontor-settings');
    }

    /** A later Http::fake() doesn't override an earlier stub, so start from a fresh factory. */
    protected function fakeKontor(string $body): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response($body)]);
    }

    public function test_managers_see_supplier_balance_on_orders_screen(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Super Admin', 'web')->givePermissionTo('manage_sims');
        Role::findOrCreate('Customer', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $customer = User::factory()->create();
        $customer->assignRole('Customer');

        $this->fakeKontor('OK|12755.23');
        Livewire::actingAs($admin)->test(Orders::class)
            ->assertSee(__('messages.sim_supplier_balance'))
            ->assertSee('12,755.23 ₺');

        // Customers never see it (and it doesn't call the API for them)
        Livewire::actingAs($customer)->test(Orders::class)->assertDontSee(__('messages.sim_supplier_balance'));
        Http::assertSentCount(1);

        // Not connected yet → clear hint for the team
        Cache::forget('kontor86_balance');
        $this->fakeKontor('NOK|API_BAGLANTI_HATASI');
        Livewire::actingAs($admin)->test(Orders::class)
            ->assertSee(__('messages.sim_supplier_not_connected'))
            ->assertSee('API_BAGLANTI_HATASI');

        // Not configured → nothing shown, no request
        Cache::forget('kontor86_balance');
        $this->fakeKontor('OK|1.00');
        config(['services.kontor.password' => null]);
        Livewire::actingAs($admin)->test(Orders::class)
            ->assertDontSee(__('messages.sim_supplier_balance'))
            ->assertDontSee(__('messages.sim_supplier_not_connected'));
        Http::assertNothingSent();
    }
}
