<?php

namespace Tests\Feature;

use App\Livewire\Sim\BuyPackage;
use App\Livewire\Sim\KontorSettings;
use App\Livewire\Sim\Orders;
use App\Livewire\Sim\PackagesManager;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Models\User;
use App\Services\KontorApiService;
use App\Services\SimOrderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Two SIM suppliers on the same ZNET API (kontor86 + telekomtl, 2026-11-22):
 * primary first, automatic fallback on supplier-side refusals, one emergency switch each.
 */
class SimTwoSuppliersTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected SimPackage $fiveGb;

    /** Next answers per supplier (host), e.g. ['kontor86' => 'OK|1|…', 'telekomtl' => 'OK|3|…'] */
    protected array $send = [];
    protected array $check = [];

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        app()->setLocale('ar');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Super Admin', 'web')->givePermissionTo('manage_sims');
        Role::findOrCreate('Customer', 'web');

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole('Super Admin');
        $this->customer = User::factory()->create(['name' => 'Shop', 'balance' => 1000]);
        $this->customer->assignRole('Customer');

        config([
            'services.kontor.dealer_code' => '5392065499', 'services.kontor.password' => 'k-secret',
            'services.telekomtl.dealer_code' => '5392065497', 'services.telekomtl.password' => 't-secret',
            'whatsapp.providers.wakeel.api_key' => 'test-key',
        ]);

        $this->send = ['kontor86' => 'OK|1|Talebiniz İşleme Alınmıştır.|215.00|12000.0000', 'telekomtl' => 'OK|1|Talebiniz İşleme Alınmıştır.|210.00|3750.0000'];
        $this->check = ['kontor86' => '', 'telekomtl' => ''];

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();
            $supplier = str_contains($url, 'telekomtl') ? 'telekomtl' : 'kontor86';
            if (str_contains($url, 'tl_servis.php')) {
                return Http::response($this->send[$supplier]);
            }
            if (str_contains($url, 'tl_kontrol.php')) {
                return Http::response($this->check[$supplier]);
            }
            if (str_contains($url, 'bakiye_kontrol.php')) {
                return Http::response($supplier === 'telekomtl' ? 'OK|3960.00|0.00' : 'OK|12000.00|200.00');
            }
            if (str_contains($url, 'provider.wakeel.cc')) {
                return Http::response(['status' => true]);
            }
            if (str_contains($url, 'api.ipify.org')) {
                return Http::response('76.13.149.149');
            }

            return Http::response('unexpected', 500);
        });

        $this->fiveGb = SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_5gb_30gun')->firstOrFail(); // kontor86 key from the migrations
        $this->fiveGb->update(['telekomtl_operator' => 'Callback', 'telekomtl_tip' => 'TeleCard', 'telekomtl_code' => '26240']);
    }

    protected function sends(): \Illuminate\Support\Collection
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'tl_servis.php'))
            ->map(fn ($pair) => [str_contains($pair[0]->url(), 'telekomtl') ? 'telekomtl' : 'kontor86', $pair[0]]);
    }

    protected function groupMessage(): string
    {
        return (string) Http::recorded(fn (Request $r) => str_contains($r->url(), 'provider.wakeel.cc'))->last()[0]['message'];
    }

    public function test_telekomtl_uses_its_own_url_account_and_http(): void
    {
        $api = KontorApiService::for('telekomtl');
        $this->assertSame(['ok' => true, 'balance' => 3960.0, 'error' => null], $api->balance());

        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'http://bayi.telekomtl.com/servis/bakiye_kontrol.php')
            && $r['kod'] === '5392065497' && $r['sifre'] === 't-secret');
        $this->assertSame('TELEKOMTL_API_PASSWORD', $api->settingKey('API_PASSWORD'));
        $this->assertSame('KONTOR_API_PASSWORD', KontorApiService::for('kontor86')->settingKey('API_PASSWORD')); // unchanged keys
        $this->assertNotSame(KontorApiService::for('kontor86')->balanceCacheKey(), $api->balanceCacheKey());
    }

    public function test_orders_go_to_the_primary_supplier_and_are_checked_there(): void
    {
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->assertSame([['kontor86']], $this->sends()->map(fn ($s) => [$s[0]])->all());
        $this->assertSame('kontor86', $order->api_supplier);
        $this->assertStringContainsString('🤖 جاري التحميل · kontor86', $this->groupMessage());

        // The primary is switched to telekomtl (cheaper): new orders go there first, with its own key
        KontorApiService::setPrimarySupplier('telekomtl');
        $second = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234568');
        [$supplier, $request] = $this->sends()->last();
        $this->assertSame('telekomtl', $supplier);
        $this->assertSame(['Callback', 'TeleCard', '26240', '5392065497'], [$request['operator'], $request['tip'], $request['kontor'], $request['bayi_kodu']]);
        $this->assertSame('telekomtl', $second->api_supplier);
        $this->assertEquals(210, $second->api_cost);

        // Each load is checked at its own supplier
        $this->check = ['kontor86' => '1:x:215.00:5051234567::a', 'telekomtl' => '3:Numara Tele CARD Değil'];
        Artisan::call('sims:check-api');
        $this->assertSame(SimOrder::API_SUCCESS, $order->fresh()->api_status);
        $this->assertSame(SimOrder::STATUS_REJECTED, $second->fresh()->status); // cancelled at telekomtl → refunded
        $this->assertEquals(730, $this->customer->fresh()->balance);              // 1000 − 270 (second one refunded)
    }

    public function test_low_balance_at_the_primary_moves_the_order_to_the_next_supplier(): void
    {
        $this->send['kontor86'] = 'OK|3|Bu İşlem İçin Bakiyeniz Yeterli Değildir. - BAK=35.0000 AL=215.00|215.00|';

        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');

        $this->assertSame(['kontor86', 'telekomtl'], $this->sends()->map(fn ($s) => $s[0])->all());
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);
        $this->assertSame('telekomtl', $order->api_supplier);
        $this->assertSame(SimOrder::STATUS_COMPLETED, $order->status);
        $this->assertArrayHasKey('kontor86', $order->apiRefusals());

        $message = $this->groupMessage();
        $this->assertStringContainsString('🤖 جاري التحميل · telekomtl', $message);
        $this->assertStringContainsString('🚨 ' . __('messages.sim_group_supplier_low_balance', ['supplier' => 'kontor86']), $message);

        // The buyer never sees supplier details; the team sees where it went and why
        Livewire::actingAs($this->customer)->test(Orders::class)->assertDontSee('kontor86')->assertDontSee('BAK=');
        Livewire::actingAs($this->admin)->test(Orders::class)
            ->assertSee(__('messages.sim_api_tried_before'))
            ->assertSee('telekomtl');
    }

    public function test_inactive_product_at_the_primary_moves_on_and_disables_only_that_key(): void
    {
        $this->send['kontor86'] = 'OK|3|Aktif Kontor callbacktelecard26240|0.00|';

        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');

        $this->assertSame('telekomtl', $order->api_supplier);
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);
        $this->fiveGb->refresh();
        $this->assertFalse($this->fiveGb->isApiReadyFor('kontor86'));
        $this->assertTrue($this->fiveGb->isApiReadyFor('telekomtl'));
        $this->assertStringContainsString('↪️ ' . __('messages.sim_group_supplier_refused', ['supplier' => 'kontor86', 'details' => 'Aktif Kontor callbacktelecard26240']), $this->groupMessage());
    }

    public function test_no_balance_anywhere_refunds_and_inactive_everywhere_goes_manual(): void
    {
        $this->send = [
            'kontor86' => 'OK|3|Bu İşlem İçin Bakiyeniz Yeterli Değildir. - BAK=35.0000 AL=215.00|215.00|',
            'telekomtl' => 'OK|3|Bu İşlem İçin Bakiyeniz Yeterli Değildir. - BAK=10.0000 AL=210.00|210.00|',
        ];
        $refunded = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->assertSame(SimOrder::STATUS_REJECTED, $refunded->status);
        $this->assertEquals(1000, $this->customer->fresh()->balance);
        $this->assertCount(2, $refunded->apiRefusals());

        $this->send = ['kontor86' => 'OK|3|Aktif Kontor x|0.00|', 'telekomtl' => 'OK|3|Aktif Kontor y|0.00|'];
        $manual = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234568');
        $this->assertSame(SimOrder::STATUS_COMPLETED, $manual->status); // paid, loaded by hand
        $this->assertNull($manual->api_status);
        $this->assertStringContainsString('⚙️', $this->groupMessage());
    }

    public function test_order_specific_refusals_and_unknown_answers_never_try_another_supplier(): void
    {
        // Same number and amount within 10 minutes: another supplier would load it twice
        $this->send['kontor86'] = 'OK|3|Aynı Numaraya Aynı Miktarda 10 Dakika İçinde İşlem Gerçekleşti|0.00';
        $refused = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->assertSame(SimOrder::STATUS_REJECTED, $refused->status);
        $this->assertCount(1, $this->sends());

        // No clear answer: it may have loaded → wait for the check, never resend elsewhere
        $this->send['kontor86'] = '8:Api İd Gönderim Hatası';
        $unknown = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234568');
        $this->assertSame(SimOrder::API_UNKNOWN, $unknown->api_status);
        $this->assertSame('kontor86', $unknown->api_supplier);
        $this->assertCount(2, $this->sends());
    }

    public function test_each_supplier_has_its_own_switch(): void
    {
        Livewire::actingAs($this->admin)->test(Orders::class)
            ->call('pauseAutoSend', 'kontor86')
            ->assertSee(__('messages.sim_auto_send_paused_flash', ['supplier' => 'kontor86']))
            ->assertDontSee(__('messages.sim_auto_send_banner')); // telekomtl still sends

        $this->assertStringContainsString(__('messages.sim_group_auto_send_paused_switched', ['supplier' => 'kontor86', 'other' => 'telekomtl']), $this->groupMessage());

        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->assertSame('telekomtl', $order->api_supplier);

        // Both paused → manual, with the banner
        Livewire::actingAs($this->admin)->test(Orders::class)
            ->call('pauseAutoSend', 'telekomtl')
            ->assertSee(__('messages.sim_auto_send_banner'));
        $manual = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234568');
        $this->assertNull($manual->api_status);
        $this->assertStringContainsString('⏸️ ' . __('messages.sim_group_api_paused'), $this->groupMessage());
        $this->assertCount(1, $this->sends());

        Livewire::actingAs($this->customer)->test(BuyPackage::class)->assertDontSee(__('messages.sim_instant_load'));
    }

    public function test_settings_screen_shows_both_suppliers_and_sets_the_primary(): void
    {
        Livewire::actingAs($this->admin)->test(KontorSettings::class)
            ->assertSee(__('messages.sim_suppliers_title'))
            ->assertSee('3,960.00 ₺')
            ->assertSee('12,000.00 ₺')
            ->call('makePrimary', 'telekomtl')
            ->assertSee(__('messages.sim_supplier_primary_saved', ['supplier' => 'telekomtl']));
        $this->assertSame('telekomtl', KontorApiService::primarySupplier());

        // Credentials of telekomtl are saved under their own keys
        Livewire::actingAs($this->admin)->test(KontorSettings::class)
            ->call('selectSupplier', 'telekomtl')
            ->assertSet('dealerCode', '5392065497')
            ->set('password', 'new-pass')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee(__('messages.kontor_connected'));
        $this->assertSame('new-pass', \App\Models\Setting::where('key', 'TELEKOMTL_API_PASSWORD')->value('value'));
        $this->assertNull(\App\Models\Setting::where('key', 'KONTOR_API_PASSWORD')->value('value'));

        Livewire::actingAs($this->customer)->test(KontorSettings::class)->assertForbidden();
    }

    public function test_package_screen_saves_the_telekomtl_key_all_or_nothing(): void
    {
        $tenGb = SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_10gb_30gun')->firstOrFail();

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $tenGb->id)
            ->set('tkOperator', 'Callback')->set('tkTip', '')->set('tkCode', '26241')
            ->call('save')
            ->assertHasErrors('tkCode');

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $tenGb->id)
            ->set('tkOperator', ' Callback ')->set('tkTip', 'TeleCard')->set('tkCode', '26241')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('🤖 telekomtl Callback·TeleCard·26241');

        $this->assertSame(['Callback', 'TeleCard', '26241'], $tenGb->fresh()->productKey('telekomtl'));
        $this->assertSame(['callback', 'telecard', '26241'], $tenGb->fresh()->productKey('kontor86')); // untouched
    }

    public function test_both_balances_show_for_managers(): void
    {
        Livewire::actingAs($this->admin)->test(Orders::class)
            ->assertSee(__('messages.sim_supplier_balance_of', ['supplier' => 'kontor86']))
            ->assertSee(__('messages.sim_supplier_balance_of', ['supplier' => 'telekomtl']))
            ->assertSee('3,960.00 ₺');
    }
}
