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
        // The group is told which paused supplier(s) the package depends on
        $this->assertStringContainsString('⏸️ ' . __('messages.sim_group_api_paused', ['suppliers' => 'kontor86، telekomtl']), $this->groupMessage());
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

    public function test_europe_goes_to_telekomtl_first_with_its_panel_codes(): void
    {
        // From telekomtl's Callback list: "Callback (TeleCard) Avrupa 5/10/20 GB" = 1233 / 1234 / 12346
        $europe = SimPackage::where('region', 'europe')->ordered()->get();
        $this->assertSame([
            'AVRUPA_FİZİKSEL_30gun_5gb' => ['Callback', 'TeleCard', '1233'],
            'AVRUPA_FİZİKSEL_30gun_10gb' => ['Callback', 'TeleCard', '1234'],
            'AVRUPA_FİZİKSEL_30gun_20gb' => ['Callback', 'TeleCard', '12346'],
        ], $europe->mapWithKeys(fn ($p) => [$p->supplier_code => $p->productKey('telekomtl')])->all());
        $this->assertSame(['telekomtl'], $europe->pluck('preferred_supplier')->unique()->values()->all());

        // kontor86 stays the primary supplier, but a Europe order goes to telekomtl first
        $this->customer->update(['balance' => 5000]);
        $this->assertSame('kontor86', KontorApiService::primarySupplier());
        $tenGb = $europe->firstWhere('supplier_code', 'AVRUPA_FİZİKSEL_30gun_10gb');
        $order = app(SimOrderService::class)->placeOrder($this->customer, $tenGb->id, '5021684186');

        [$supplier, $request] = $this->sends()->sole();
        $this->assertSame('telekomtl', $supplier);
        $this->assertSame(['Callback', 'TeleCard', '1234', '5021684186'], [$request['operator'], $request['tip'], $request['kontor'], $request['gsmno']]);
        $this->assertSame('telekomtl', $order->api_supplier);

        // Turkey packages are not at telekomtl: they keep going to kontor86
        app(SimOrderService::class)->placeOrder($this->customer, SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_10gb_30gun')->value('id'), '5051234567');
        $this->assertSame('kontor86', $this->sends()->last()[0]);
    }

    public function test_preferred_supplier_falls_back_to_the_other_one(): void
    {
        $tenGb = SimPackage::where('supplier_code', 'AVRUPA_FİZİKSEL_30gun_10gb')->firstOrFail();
        $this->send['telekomtl'] = 'OK|3|Bu İşlem İçin Bakiyeniz Yeterli Değildir. - BAK=10.0000 AL=295.00|295.00|';

        $order = app(SimOrderService::class)->placeOrder($this->customer, $tenGb->id, '5021684186');

        $this->assertSame(['telekomtl', 'kontor86'], $this->sends()->map(fn ($s) => $s[0])->all());
        $this->assertSame('kontor86', $order->api_supplier);
        $this->assertSame(['Callback', 'eSIM', '45241'], [$this->sends()->last()[1]['operator'], $this->sends()->last()[1]['tip'], $this->sends()->last()[1]['kontor']]);
    }

    public function test_package_screen_sets_the_preferred_supplier(): void
    {
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $this->fiveGb->id)
            ->assertSet('preferredSupplier', '')
            ->set('preferredSupplier', 'mars')
            ->call('save')
            ->assertHasErrors('preferredSupplier')
            ->set('preferredSupplier', 'telekomtl')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('telekomtl', $this->fiveGb->fresh()->preferred_supplier);

        app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->assertSame('telekomtl', $this->sends()->sole()[0]);

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $this->fiveGb->id)
            ->set('preferredSupplier', '')
            ->call('save');
        $this->assertNull($this->fiveGb->fresh()->preferred_supplier);
    }

    public function test_europe_key_migration_keeps_what_an_admin_set(): void
    {
        $five = SimPackage::where('supplier_code', 'AVRUPA_FİZİKSEL_30gun_5gb')->firstOrFail();
        $five->update(['telekomtl_operator' => 'X', 'telekomtl_tip' => 'Y', 'telekomtl_code' => '9', 'preferred_supplier' => 'kontor86']);

        (require database_path('migrations/2026_11_22_000002_set_telekomtl_europe_keys.php'))->up();

        $this->assertSame(['X', 'Y', '9'], $five->fresh()->productKey('telekomtl'));
        $this->assertSame('kontor86', $five->fresh()->preferred_supplier);
    }

    public function test_turk_telekom_esim_packages_load_through_telekomtl(): void
    {
        $tt = SimPackage::where('network_name', 'Türk Telekom')->ordered()->get();
        $this->assertSame([
            'TURKTELEKOM_ESIM_5gb_30gun' => [200, '115', true],
            'TURKTELEKOM_ESIM_10gb_30gun' => [300, '175', true],    // on sale since 2026-11-24 (owner's decision)
            'TURKTELEKOM_ESIM_20gb_30gun' => [450, '330', true],
            'TURKTELEKOM_ESIM_30gb_30gun' => [600, '480', true],
            'TURKTELEKOM_ESIM_50gb_30gun' => [750, '590', true],
            'TURKTELEKOM_ESIM_50gb_180gun' => [900, '2000', true],
            'TURKTELEKOM_ESIM_100gb_90gun' => [1900, '909090', true],
        ], $tt->mapWithKeys(fn ($p) => [$p->supplier_code => [(int) $p->price, $p->telekomtl_code, $p->is_active]])->all());
        foreach ($tt as $package) {
            $this->assertSame(['local', 'esim', 'telekomtl'], [$package->region, $package->sim_type, $package->preferred_supplier]);
            $this->assertFalse($package->isApiReadyFor('kontor86'));
        }
        $this->assertSame('3 أشهر', $tt->firstWhere('data_gb', 100)->durationLabel());

        // In the Turkey eSIM tab, under their own network heading
        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectRegion', 'local_esim')
            ->assertSee(__('messages.sim_network_packages_count', ['count' => 7]))
            ->assertSee('باقة Türk Telekom 5 جيجا - 30 يوم (eSIM)')
            ->assertSee('باقة Türk Telekom 10 جيجا - 30 يوم (eSIM)')
            ->assertSee('باقة تركيا 5 جيجا - 30 يوم (eSIM)'); // Tele CARD eSIMs stay in the same tab, own heading

        // An order goes to telekomtl with its key and the Turkish number
        $five = $tt->firstWhere('supplier_code', 'TURKTELEKOM_ESIM_5gb_30gun');
        $this->send['telekomtl'] = 'OK|1|Talebiniz İşleme Alınmıştır.|132.00|3828.0000';
        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectPackage', $five->id)
            ->set('phoneNumber', '0555 123 45 67')
            ->call('placeOrder')
            ->assertHasNoErrors();

        [$supplier, $request] = $this->sends()->sole();
        $this->assertSame('telekomtl', $supplier);
        $this->assertSame(['Callback', 'eSIM', '115', '5551234567'], [$request['operator'], $request['tip'], $request['kontor'], $request['gsmno']]);
        $order = SimOrder::sole();
        $this->assertEquals(132, $order->api_cost);
        $this->assertEquals(68, $order->profit());
    }

    public function test_tele_card_order_while_kontor86_is_paused_explains_why_it_is_manual(): void
    {
        // Production state 2026-11-24: telekomtl primary and active, kontor86 paused, Tele CARD 5 GB only at kontor86
        $this->fiveGb->update(['telekomtl_operator' => null, 'telekomtl_tip' => null, 'telekomtl_code' => null]);
        KontorApiService::setPrimarySupplier('telekomtl');
        KontorApiService::for('kontor86')->setAutoSendPaused(true, $this->admin);

        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5026374700');

        $this->assertNull($order->api_status); // manual: nothing sent anywhere
        $this->assertCount(0, $this->sends());
        $this->assertStringContainsString('⏸️ التحميل التلقائي عبر kontor86 متوقف حالياً، والطلب بانتظار التحميل اليدوي', $this->groupMessage());

        // The suppliers screen says which packages depend on the paused supplier
        $exclusive = KontorApiService::overview()['kontor86']['exclusive'];
        $this->assertContains('باقة تركيا 5 جيجا - 30 يوم (فيزيائية)', $exclusive);
        $this->assertNotContains('باقة Türk Telekom 5 جيجا - 30 يوم (eSIM)', $exclusive);

        Livewire::actingAs($this->admin)->test(KontorSettings::class)
            ->assertSee(__('messages.sim_supplier_exclusive_paused', ['count' => count($exclusive), 'supplier' => 'kontor86']))
            ->assertSee('باقة تركيا 5 جيجا - 30 يوم (فيزيائية)');
        Livewire::actingAs($this->admin)->test(\App\Livewire\Sim\Orders::class)
            ->assertSee(__('messages.sim_supplier_manual_now', ['count' => count($exclusive)]));

        // Resuming kontor86: the next Tele CARD order is sent automatically again
        KontorApiService::for('kontor86')->setAutoSendPaused(false);
        $next = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5026374701');
        $this->assertSame('kontor86', $next->api_supplier);
    }

    public function test_connection_test_shows_the_selected_supplier_balance(): void
    {
        Livewire::actingAs($this->admin)->test(KontorSettings::class)
            ->call('selectSupplier', 'telekomtl')
            ->call('testConnection')
            ->assertSee(__('messages.sim_supplier_balance_of', ['supplier' => 'telekomtl']) . ': <span', false)
            ->assertSee('3,960.00 ₺');
    }

    public function test_a_package_added_by_hand_is_linked_by_picking_it_from_the_supplier_list(): void
    {
        // Like the teacher's packages: added in the screen, physical, no supplier key
        $manual = SimPackage::create([
            'region' => 'local', 'sim_type' => 'physical', 'network_name' => 'Tele CARD', 'data_gb' => 30, 'validity_days' => 30,
            'price' => 550, 'name_ar' => 'الاستاذ', 'name_tr' => 'Hoca', 'is_active' => true, 'sort_order' => 99,
        ]);

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->assertSee(__('messages.sim_unlinked_title', ['count' => 1]))
            ->assertSee('الاستاذ')
            ->call('edit', $manual->id)
            ->assertSet('pickKontor', '')
            ->set('pickKontor', 'TeleCard|26257') // FİZİKSEL Turkiye 30 GB · 30 gün, cost 555 (list of 2026-11-25)
            ->assertSet('apiOperator', 'Callback')
            ->assertSet('apiTip', 'TeleCard')
            ->assertSet('apiCode', '26257')
            ->assertSee(__('messages.sim_pick_loss', ['amount' => '5'])) // 550 < 555
            ->set('price', 650)
            ->assertSee(__('messages.sim_api_profit'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertDontSee(__('messages.sim_unlinked_title', ['count' => 1]));

        $manual->refresh();
        $this->assertSame(['Callback', 'TeleCard', '26257'], $manual->productKey('kontor86'));
        $this->assertEquals(555, $manual->listedCost('kontor86'));

        // Its orders now load automatically
        $this->customer->update(['balance' => 1000]);
        $order = app(SimOrderService::class)->placeOrder($this->customer, $manual->id, '5051234567');
        [$supplier, $request] = $this->sends()->sole();
        $this->assertSame('kontor86', $supplier);
        $this->assertSame('26257', $request['kontor']);
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);

        // Editing again shows the picked product; clearing the pick unlinks that supplier
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $manual->id)
            ->assertSet('pickKontor', 'TeleCard|26257')
            ->set('pickKontor', '')
            ->assertSet('apiCode', '');
    }

    public function test_telekomtl_codes_with_a_dot_can_be_linked(): void
    {
        $esim = SimPackage::create([
            'region' => 'local', 'sim_type' => 'esim', 'network_name' => 'Turkcell', 'data_gb' => 10, 'validity_days' => 30,
            'price' => 450, 'name_ar' => 'Turkcell 10', 'name_tr' => 'Turkcell 10', 'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $esim->id)
            ->set('pickTk', 'eSIM|100.01')
            ->assertSet('tkCode', '100.01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['Callback', 'eSIM', '100.01'], $esim->fresh()->productKey('telekomtl'));
        $this->assertEquals(360, $esim->fresh()->listedCost('telekomtl'));
    }

    public function test_supplier_lists_have_unique_keys(): void
    {
        foreach (['kontor86', 'telekomtl'] as $supplier) {
            $keys = collect(SimPackage::supplierProducts($supplier))->map(fn ($p) => $p['tip'] . '|' . $p['code']);
            $this->assertSame($keys->count(), $keys->unique()->count(), $supplier);
            $this->assertNotEmpty($keys);
        }
        // The keys already used by the seeded packages are in the lists
        $this->assertEquals(171, SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_5gb_30gun')->first()->listedCost('kontor86'));
        $this->assertEquals(295, SimPackage::where('supplier_code', 'AVRUPA_FİZİKSEL_30gun_10gb')->first()->listedCost('telekomtl'));
    }

    public function test_hizli_packages_are_on_sale_and_linked_to_kontor86_only(): void
    {
        $fast = SimPackage::where('is_fast', true)->orderBy('data_gb')->get();
        $this->assertSame([5, 10, 20, 50, 100], $fast->pluck('data_gb')->all());
        // «Kontor Fiyat Ayarları» → Callback, API column (2026-11-25)
        $this->assertSame(['7036449', '6987918', '2652690', '2285493', '2205285'], $fast->pluck('api_code')->all());
        $this->assertSame([150, 220, 310, 570, 1070], $fast->pluck('price')->map(fn ($p) => (int) $p)->all()); // owner's prices
        foreach ($fast as $package) {
            $this->assertTrue($package->is_active);
            $this->assertSame(['Callback', 'TeleCard'], [$package->api_operator, $package->api_tip]);
            $this->assertNull($package->productKey('telekomtl'));       // telekomtl has no HIZLI product
            $this->assertSame('Tele CARD', $package->network_name);
            $this->assertSame(30, $package->validity_days);
        }
        $this->assertSame('باقة تركيا سريعة 10 جيجا - 30 يوم (فيزيائية)', $fast[1]->name_ar);
        $this->assertSame('Türkiye Hızlı 10 GB - 30 Gün (Fiziksel)', $fast[1]->name_tr);
        $this->assertEquals(190, $fast[1]->listedCost('kontor86'));

        // Specs never mix: ordinary packages keep their ordinary product, fast ones only take HIZLI
        $this->assertSame(['26240'], array_column($this->fiveGb->matchingProducts('kontor86'), 'code'));
        $this->assertSame(['7036449'], array_column($fast[0]->matchingProducts('kontor86'), 'code'));
        $this->assertSame([], $fast[0]->matchingProducts('telekomtl'));
    }

    public function test_a_fast_package_added_in_the_screen_links_itself_to_hizli(): void
    {
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('create')
            ->set('region', 'local')->set('simType', 'physical')->set('networkName', 'Tele CARD')
            ->set('dataGb', 20)->set('validityDays', 30)
            ->assertSee('26250')                 // ordinary package → ordinary product
            ->set('isFast', true)
            ->assertSee(__('messages.sim_fast_package'))
            ->assertSee('2652690')               // fast → HIZLI FİZİKSEL 20 GB
            ->set('price', 400)
            ->call('save')
            ->assertHasNoErrors();

        $package = SimPackage::where('is_fast', true)->latest('id')->first();
        $this->assertSame(['Callback', 'TeleCard', '2652690'], $package->productKey('kontor86'));
        $this->assertNull($package->productKey('telekomtl'));
        $this->assertSame('باقة تركيا سريعة 20 جيجا - 30 يوم (فيزيائية)', $package->name_ar);
        $this->assertTrue($package->is_active);
    }

    public function test_the_teachers_fast_card_becomes_the_hizli_50gb(): void
    {
        // Production on 2026-11-26: the teacher's own 50 GB card, linked on save to the ordinary products
        // (kontor86 665 / telekomtl 615), both above its 575 ₺ price
        SimPackage::where('is_fast', true)->where('data_gb', 50)->forceDelete();
        $card = $this->teacherPackage(50, 30, 575, [
            'name_ar' => 'باقة سريعة', 'name_tr' => 'Hızlı paket', 'description_ar' => 'هذا الباقة سريعة جدا', 'network_name' => 'Türk Telekom',
            'api_operator' => 'Callback', 'api_tip' => 'TeleCard', 'api_code' => '26260',
            'telekomtl_operator' => 'Callback', 'telekomtl_tip' => 'eSIM', 'telekomtl_code' => '590',
        ]);

        (require database_path('migrations/2026_11_26_000001_add_fast_hizli_packages.php'))->up();

        $card->refresh();
        $this->assertTrue($card->is_fast);
        $this->assertSame('Tele CARD', $card->network_name);
        $this->assertSame(['Callback', 'TeleCard', '2285493'], $card->productKey('kontor86'));
        $this->assertNull($card->productKey('telekomtl'));
        $this->assertSame(['باقة سريعة', 575.0, true], [$card->name_ar, (float) $card->price, $card->is_active]); // his texts, price, on sale
        $this->assertSame(1, SimPackage::where('is_fast', true)->where('data_gb', 50)->count()); // not added twice

        // Its orders go to kontor86's HIZLI tile, and the group sees which tile it is
        KontorApiService::setPrimarySupplier('telekomtl');
        $this->customer->update(['balance' => 5000]);
        app(SimOrderService::class)->placeOrder($this->customer, $card->id, '5051234567');
        [$supplier, $request] = $this->sends()->sole();
        $this->assertSame(['kontor86', '2285493'], [$supplier, $request['kontor']]);
        $this->assertStringContainsString('تركيا 50 جيجا - 30 يوم ⚡ سريعة (HIZLI)', $this->groupMessage());

        // kontor86 paused: it waits for a manual load, never goes to telekomtl at a loss
        KontorApiService::for('kontor86')->setAutoSendPaused(true, $this->admin);
        $second = app(SimOrderService::class)->placeOrder($this->customer, $card->id, '5051234568');
        $this->assertNull($second->api_status);
        $this->assertCount(1, $this->sends());
        $this->assertStringContainsString('⏸️', $this->groupMessage());
    }

    public function test_a_deleted_fast_package_comes_back_at_the_owners_price(): void
    {
        // The teacher's converted 50 GB card, deleted by him afterwards
        SimPackage::where('is_fast', true)->where('data_gb', 50)->first()->delete();
        SimPackage::where('is_fast', true)->where('data_gb', 10)->update(['price' => 999, 'is_active' => false]);

        (require database_path('migrations/2026_11_26_000002_price_and_open_hizli_packages.php'))->up();
        (require database_path('migrations/2026_11_26_000002_price_and_open_hizli_packages.php'))->up(); // idempotent

        $fifty = SimPackage::where('is_fast', true)->where('data_gb', 50)->sole();
        $this->assertSame(['Callback', 'TeleCard', '2285493'], $fifty->productKey('kontor86'));
        $this->assertEquals(570, $fifty->price);
        $this->assertTrue($fifty->is_active);
        $ten = SimPackage::where('is_fast', true)->where('data_gb', 10)->sole();
        $this->assertEquals(220, $ten->price);
        $this->assertTrue($ten->is_active);
    }

    public function test_an_api_code_typed_in_the_package_name_field_is_moved_to_its_place(): void
    {
        // What the teacher did: 2285493 in «اسم الباقة عند المورد», the key fields left empty
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('create')
            ->set('region', 'local')->set('simType', 'physical')->set('networkName', 'Tele CARD')
            ->set('dataGb', 50)->set('validityDays', 30)->set('price', 570)
            ->set('pickTk', 'eSIM|590')                      // TURKTELEKOM 50 GB, an ordinary product
            ->set('supplierCode', '2285493')
            ->assertSee(__('messages.sim_supplier_code_is_api', ['code' => '2285493', 'product' => 'HIZLI FİZİKSEL 50 GB · 30 gün', 'supplier' => 'kontor86']))
            ->call('useSupplierCodeAsKey')
            ->assertSet('apiOperator', 'Callback')->assertSet('apiTip', 'TeleCard')->assertSet('apiCode', '2285493')
            ->assertSet('isFast', true)
            ->assertSet('tkCode', '')                         // fast packages never load an ordinary product
            ->assertSet('supplierCode', 'HIZLI FİZİKSEL 50 GB · 30 gün')
            ->assertDontSee(__('messages.sim_supplier_code_move'))
            ->call('save')
            ->assertHasNoErrors();

        $package = SimPackage::latest('id')->first();
        $this->assertTrue($package->is_fast);
        $this->assertSame(['Callback', 'TeleCard', '2285493'], $package->productKey('kontor86'));
        $this->assertNull($package->productKey('telekomtl'));

        // A package name in that field raises nothing
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('create')
            ->set('supplierCode', 'FİZİKSEL Turkiye_5gb_30gun')
            ->assertDontSee(__('messages.sim_supplier_code_move'));
    }

    public function test_fast_cards_stand_out_on_the_buy_screen(): void
    {
        $fast = SimPackage::where('is_fast', true)->where('data_gb', 10)->firstOrFail();

        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            // Their own category card, next to Turkey / eSIM / Europe (2026-11-26)
            ->assertSee(__('messages.sim_tab_local_fast'))
            ->assertSee(__('messages.sim_category_from', ['price' => '150']))
            ->assertDontSee($fast->name_ar)                       // not mixed into «باقات تركيا»
            ->call('selectRegion', 'local_fast')
            ->assertSee($fast->name_ar)
            ->assertSee(__('messages.sim_fast_ribbon'))
            ->assertSeeHtml('from-amber-400 via-orange-500 to-orange-700')
            ->assertSee('باقة تركيا سريعة 5 جيجا')
            ->assertDontSee($this->fiveGb->name_ar)
            ->call('selectPackage', $this->fiveGb->id)
            ->assertSet('region', 'local');                       // picking a package opens its category

        // Each kind of package has its own colors
        $this->assertSame('local', $this->fiveGb->cardTheme());
        $this->assertSame('fast', $fast->cardTheme());
        $this->assertSame('local_esim', SimPackage::where('supplier_code', 'E-SİM Turkiye_5gb_30gun')->first()->cardTheme());
        $this->assertSame('tt_esim', SimPackage::where('network_name', 'Türk Telekom')->first()->cardTheme());
        $this->assertSame('europe', SimPackage::where('region', 'europe')->first()->cardTheme());
        $this->assertSame('local_fast', $fast->tabKey());

        // Admin: the same cards, the open one lists its packages
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->assertSee(__('messages.sim_tab_local_fast'))
            ->assertDontSee($fast->name_ar)
            ->call('selectCategory', 'local_fast')
            ->assertSee($fast->name_ar)
            ->call('edit', $fast->id)
            ->set('price', 230)
            ->call('save')
            ->assertSet('category', 'local_fast');
    }

    protected function teacherPackage(int $gb, int $days, float $price, array $extra = []): SimPackage
    {
        return SimPackage::create($extra + [
            'region' => 'local', 'sim_type' => 'physical', 'data_gb' => $gb, 'validity_days' => $days, 'price' => $price,
            'name_ar' => 'الاستاذ', 'name_tr' => 'Hoca', 'description_ar' => 'قم بداخل اخر 8ارقام', 'is_active' => true, 'sort_order' => 90,
        ]);
    }

    public function test_migration_links_the_teachers_packages_by_their_specs(): void
    {
        // Exactly what the teacher added on 2026-11-24 (no network, no key)
        $five = $this->teacherPackage(5, 30, 100);
        $thirty = $this->teacherPackage(30, 30, 550);
        $hundred = $this->teacherPackage(100, 180, 1700);
        $before = SimPackage::where('name_ar', '!=', 'الاستاذ')->get()->mapWithKeys(fn ($p) => [$p->id => [$p->productKey('kontor86'), $p->productKey('telekomtl')]]);

        (require database_path('migrations/2026_11_24_000002_link_unlinked_packages_by_specs.php'))->up();

        $this->assertSame(['Callback', 'TeleCard', '26240'], $five->fresh()->productKey('kontor86'));   // Tele CARD physical 5 GB · 30 days
        $this->assertSame(['Callback', 'TeleCard', '26257'], $thirty->fresh()->productKey('kontor86')); // Tele CARD physical 30 GB · 30 days
        $this->assertSame(['Callback', 'eSIM', '115'], $five->fresh()->productKey('telekomtl'));        // Türk Telekom 5 GB loads on Tele CARD lines
        $this->assertSame('telekomtl', $five->fresh()->preferred_supplier);                            // cheaper: 132 < 215
        $this->assertFalse($hundred->fresh()->isApiReady()); // no 100 GB · 180 days product anywhere: stays manual

        // Packages that already had keys are untouched
        foreach ($before as $id => [$kontor, $telekom]) {
            $package = SimPackage::find($id);
            $this->assertSame([$kontor, $telekom], [$package->productKey('kontor86'), $package->productKey('telekomtl')]);
        }

        // The 5 GB now loads automatically, at the cheaper supplier
        $order = app(SimOrderService::class)->placeOrder($this->customer, $five->id, '5026374700');
        $this->assertSame(['telekomtl', '115'], [$this->sends()->sole()[0], $this->sends()->sole()[1]['kontor']]);
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);

        // The screen explains the one left
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->assertSee(__('messages.sim_unlinked_title', ['count' => 1]))
            ->assertSee(__('messages.sim_unlinked_no_match'));
    }

    public function test_new_package_added_in_the_screen_links_itself(): void
    {
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('create')
            ->set('region', 'local')->set('simType', 'physical')
            ->set('dataGb', 20)->set('validityDays', 30)->set('price', 500)
            ->assertSee(__('messages.sim_pick_suggested'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee(__('messages.sim_package_saved_linked', ['products' => 'kontor86: FİZİKSEL Turkiye 20 GB · 30 gün (26250)']));

        $new = SimPackage::latest('id')->first();
        $this->assertSame(['Callback', 'TeleCard', '26250'], $new->productKey('kontor86'));

        // Europe: both suppliers carry it → both linked, the cheaper one preferred
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('create')
            ->set('region', 'europe')->set('simType', 'physical')
            ->set('dataGb', 50)->set('validityDays', 30)->set('price', 1400)
            ->call('save');
        $europe = SimPackage::latest('id')->first();
        $this->assertSame(['Callback', 'eSIM', '45244'], $europe->productKey('kontor86'));
        $this->assertSame(['Callback', 'TeleCard', '12347'], $europe->productKey('telekomtl'));
        $this->assertSame('telekomtl', $europe->preferred_supplier); // 975 < 995

        // A network that names another listed product: OnlineKart eSIM 5 GB · 30 days at telekomtl
        $online = $this->teacherPackage(5, 30, 175, ['network_name' => 'onlinekart', 'sim_type' => 'esim']);
        $this->assertSame(['telekomtl' => '777'], collect($online->suggestedLinks())->map(fn ($p) => $p['code'])->all());

        // An eSIM with no known network is ambiguous: never linked on its own
        $esim = $this->teacherPackage(10, 30, 300, ['sim_type' => 'esim']);
        $this->assertSame([], $esim->suggestedLinks());
    }

    public function test_one_click_link_from_the_unlinked_box(): void
    {
        $five = $this->teacherPackage(5, 30, 100);
        $hundred = $this->teacherPackage(100, 180, 1700);

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->assertSee('kontor86: FİZİKSEL Turkiye 5 GB · 30 gün · 26240')
            ->call('linkSuggested', $five->id)
            ->assertSee(__('messages.sim_package_linked', ['name' => 'الاستاذ', 'products' => 'kontor86: FİZİKSEL Turkiye 5 GB · 30 gün (26240) · telekomtl: TURKTELEKOM 5 GB · 30 gün (115)']))
            ->call('linkSuggested', $hundred->id)
            ->assertSee(__('messages.sim_package_no_match', ['name' => 'الاستاذ']));

        $this->assertTrue($five->fresh()->isApiReady());

        $thirty = $this->teacherPackage(30, 30, 550);
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('linkAllSuggested')
            ->assertSee(__('messages.sim_packages_linked_count', ['count' => 1]));
        $this->assertTrue($thirty->fresh()->isApiReady());
        $this->assertFalse($hundred->fresh()->isApiReady());
    }

    public function test_every_package_we_added_before_is_linked_to_a_supplier(): void
    {
        $unlinked = SimPackage::active()->get()->reject(fn (SimPackage $p) => $p->isApiReady())->pluck('supplier_code')->all();
        $this->assertSame([], $unlinked);
        $this->assertSame(26, SimPackage::active()->count()); // incl. the 5 HIZLI packages (2026-11-26)
    }

    public function test_tele_card_lines_load_from_telekomtl_while_kontor86_is_paused(): void
    {
        // Production on 2026-11-24: telekomtl primary, kontor86 paused (more expensive);
        // the teacher's 5 / 30 GB were linked to kontor86 only, his 100 GB "6 months" to nothing
        KontorApiService::setPrimarySupplier('telekomtl');
        KontorApiService::for('kontor86')->setAutoSendPaused(true, $this->admin);
        $five = $this->teacherPackage(5, 30, 100, ['api_operator' => 'Callback', 'api_tip' => 'TeleCard', 'api_code' => '26240']);
        $thirty = $this->teacherPackage(30, 30, 550, ['api_operator' => 'Callback', 'api_tip' => 'TeleCard', 'api_code' => '26257']);
        $hundred = $this->teacherPackage(100, 180, 1700);
        $this->fiveGb->update(['telekomtl_operator' => null, 'telekomtl_tip' => null, 'telekomtl_code' => null]);

        (require database_path('migrations/2026_11_24_000003_link_tele_card_lines_to_telekomtl.php'))->up();

        // Türk Telekom products at telekomtl, kontor86 keys kept as backup
        $this->assertSame(['Callback', 'eSIM', '115'], $five->fresh()->productKey('telekomtl'));
        $this->assertSame(['Callback', 'TeleCard', '26240'], $five->fresh()->productKey('kontor86'));
        $this->assertSame(['Callback', 'eSIM', '480'], $thirty->fresh()->productKey('telekomtl'));
        // The circled panel product: TURKTELEKOM 100 GB İnternet 90 gün → duration corrected, linked
        $this->assertSame(90, $hundred->fresh()->validity_days);
        $this->assertSame('3 أشهر', $hundred->fresh()->durationLabel());
        $this->assertSame(['Callback', 'eSIM', '909090'], $hundred->fresh()->productKey('telekomtl'));
        // Our own Tele CARD physical packages
        $this->assertSame('115', $this->fiveGb->fresh()->telekomtl_code);
        $this->assertSame([
            'FİZİKSEL Turkiye_5gb_30gun' => '115', 'FİZİKSEL Turkiye_10gb_30gun' => '175', 'FİZİKSEL Turkiye_20gb_30gun' => '330',
            'FİZİKSEL Turkiye_50gb_30gun' => '590', 'FİZİKSEL Turkiye_50gb_180gun' => '2000', 'FİZİKSEL Turkiye_100gb_30gun' => '1600',
        ], SimPackage::where('sim_type', 'physical')->where('region', 'local')->where('is_fast', false)->whereNotNull('supplier_code')->ordered()->pluck('telekomtl_code', 'supplier_code')->all());
        // Tele CARD eSIM packages load from OnlineKart eSIM at telekomtl (2026-11-25)
        $this->assertSame('777', SimPackage::where('supplier_code', 'E-SİM Turkiye_5gb_30gun')->value('telekomtl_code'));

        // A Tele CARD order now goes to telekomtl automatically (it went to the group before)
        $this->customer->update(['balance' => 5000]);
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5026374700');
        [$supplier, $request] = $this->sends()->sole();
        $this->assertSame(['telekomtl', 'Callback', 'eSIM', '115', '5026374700'], [$supplier, $request['operator'], $request['tip'], $request['kontor'], $request['gsmno']]);
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);
        $this->assertStringContainsString('🤖 جاري التحميل · telekomtl', $this->groupMessage());

        // Nothing is left unlinked
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->assertDontSee(__('messages.sim_unlinked_title', ['count' => 1]));
        // Only the HIZLI packages depend on kontor86 (telekomtl has no fast product)
        $this->assertSame(
            SimPackage::active()->where('is_fast', true)->get()->map(fn ($p) => $p->localized_name)->sort()->values()->all(),
            collect(KontorApiService::overview()['kontor86']['exclusive'])->sort()->values()->all()
        );
        $this->assertCount(5, KontorApiService::overview()['kontor86']['exclusive']);
    }

    public function test_tele_card_esim_loads_from_onlinekart_at_telekomtl(): void
    {
        // Production: telekomtl primary, kontor86 paused (more expensive)
        KontorApiService::setPrimarySupplier('telekomtl');
        KontorApiService::for('kontor86')->setAutoSendPaused(true, $this->admin);

        $esims = SimPackage::where('sim_type', 'esim')->where('network_name', 'Tele CARD')->ordered()->get();
        $this->assertSame([
            'E-SİM Turkiye_5gb_30gun' => ['OnlineKart', '777'],
            'E-SİM Turkiye_10gb_30gun' => ['OnlineKart', '1111'],
            'E-SİM Turkiye_20gb_30gun' => ['OnlineKart', '2222'],
            'E-SİM Turkiye_50gb_30gun' => ['OnlineKart', '5555'],
            'E-SİM Turkiye_50gb_180gun' => ['OnlineKart', '180180'], // now 49 GB · 180 days
        ], $esims->mapWithKeys(fn ($p) => [$p->supplier_code => [$p->telekomtl_tip, $p->telekomtl_code]])->all());

        $tenGb = $esims->firstWhere('supplier_code', 'E-SİM Turkiye_10gb_30gun');
        $this->customer->update(['balance' => 1000]);
        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectPackage', $tenGb->id)
            ->set('phoneNumber', '0555 123 45 67')
            ->call('placeOrder')
            ->assertHasNoErrors();

        [$supplier, $request] = $this->sends()->sole();
        $this->assertSame(['telekomtl', 'Callback', 'OnlineKart', '1111', '5551234567'],
            [$supplier, $request['operator'], $request['tip'], $request['kontor'], $request['gsmno']]); // customer's mobile, as the team does
        $this->assertSame(SimOrder::API_PROCESSING, SimOrder::sole()->api_status);
    }

    public function test_manager_picks_the_supplier_for_one_order(): void
    {
        // telekomtl primary; kontor86 paused because it is more expensive
        KontorApiService::setPrimarySupplier('telekomtl');
        KontorApiService::for('kontor86')->setAutoSendPaused(true, $this->admin);
        $this->admin->update(['balance' => 1000]);

        $component = Livewire::actingAs($this->admin)->test(BuyPackage::class)
            ->call('selectPackage', $this->fiveGb->id)
            ->assertSee(__('messages.sim_supplier_choice_label'))
            ->assertSee(__('messages.sim_supplier_choice_auto', ['supplier' => 'telekomtl']))
            ->assertSee(__('messages.sim_supplier_choice_paused'));

        // kontor86 chosen even though it is paused: sent there only
        $component->set('supplierChoice', 'kontor86')
            ->set('phoneNumber', '51234567')
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertSet('supplierChoice', '');

        $order = SimOrder::sole();
        [$supplier, $request] = $this->sends()->sole();
        $this->assertSame(['kontor86', '26240'], [$supplier, $request['kontor']]);
        $this->assertSame('kontor86', $order->api_supplier);
        $this->assertTrue($order->api_supplier_forced);
        $this->assertStringContainsString('🎯 ' . __('messages.sim_group_supplier_chosen', ['supplier' => 'kontor86']), $this->groupMessage());
        Livewire::actingAs($this->admin)->test(\App\Livewire\Sim\Orders::class)->assertSee(__('messages.sim_supplier_chosen_badge'));

        // Without a choice the next order follows the settings (telekomtl)
        Livewire::actingAs($this->admin)->test(BuyPackage::class)
            ->call('selectPackage', $this->fiveGb->id)
            ->set('phoneNumber', '51234568')
            ->call('placeOrder');
        $this->assertSame('telekomtl', $this->sends()->last()[0]);
        $this->assertFalse(SimOrder::latest('id')->first()->api_supplier_forced);
    }

    public function test_chosen_supplier_refusal_is_not_moved_to_the_other_one(): void
    {
        $this->send['kontor86'] = 'OK|3|Bu İşlem İçin Bakiyeniz Yeterli Değildir. - BAK=35.0000 AL=215.00|215.00|';

        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567', null, null, null, 'kontor86');

        $this->assertSame(['kontor86'], $this->sends()->map(fn ($s) => $s[0])->all()); // telekomtl never tried
        $this->assertSame(SimOrder::STATUS_REJECTED, $order->fresh()->status);         // refunded
        $this->assertEquals(1000, $this->customer->fresh()->balance);
    }

    public function test_a_supplier_that_does_not_carry_the_package_cannot_be_chosen(): void
    {
        $turkTelekom = SimPackage::where('supplier_code', 'TURKTELEKOM_ESIM_5gb_30gun')->firstOrFail(); // telekomtl only

        try {
            app(SimOrderService::class)->placeOrder($this->customer, $turkTelekom->id, '5551234567', null, null, null, 'kontor86');
            $this->fail('A supplier without this package must be refused');
        } catch (\App\Exceptions\SimOrderException $e) {
            $this->assertSame('supplier_unavailable', $e->errorCode);
        }
        $this->assertSame(0, SimOrder::count());
        $this->assertEquals(1000, $this->customer->fresh()->balance);

        // In the form it is listed but not selectable
        $this->admin->update(['balance' => 1000]);
        Livewire::actingAs($this->admin)->test(BuyPackage::class)
            ->call('selectPackage', $turkTelekom->id)
            ->assertSee(__('messages.sim_supplier_choice_not_linked'))
            ->set('supplierChoice', 'kontor86')
            ->set('phoneNumber', '5551234567')
            ->call('placeOrder')
            ->assertHasErrors('order');
        $this->assertSame(0, SimOrder::count());
    }

    public function test_manager_can_choose_a_manual_load_for_one_order(): void
    {
        KontorApiService::setPrimarySupplier('telekomtl');
        $this->admin->update(['balance' => 1000]);

        Livewire::actingAs($this->admin)->test(BuyPackage::class)
            ->call('selectPackage', $this->fiveGb->id)
            ->assertSee(__('messages.sim_supplier_choice_manual'))
            ->set('supplierChoice', 'manual')
            ->assertSee(__('messages.sim_supplier_choice_manual_hint'))
            ->set('phoneNumber', '51234567')
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertSee(__('messages.sim_supplier_choice_manual')); // receipt: «المورد: ✋ تحويل يدوي»

        $order = SimOrder::sole();
        $this->assertCount(0, $this->sends());                 // no supplier was called
        $this->assertNull($order->api_status);
        $this->assertNull($order->api_supplier);
        $this->assertTrue($order->api_supplier_forced);
        $this->assertSame(SimOrder::STATUS_COMPLETED, $order->status); // paid, loaded by hand like any manual order
        $message = $this->groupMessage();
        $this->assertStringContainsString('✋ ' . __('messages.sim_group_manual_chosen', [], 'ar'), $message);
        $this->assertStringNotContainsString('🤖', $message);
        $this->assertStringNotContainsString('🎯', $message);
        Livewire::actingAs($this->admin)->test(\App\Livewire\Sim\Orders::class)
            ->assertSee(__('messages.sim_manual_chosen_badge'))
            ->assertDontSee(__('messages.sim_supplier_chosen_badge'));

        // A customer sending "manual" is ignored: the order loads automatically as usual
        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectPackage', $this->fiveGb->id)
            ->assertDontSee(__('messages.sim_supplier_choice_manual'))
            ->set('supplierChoice', 'manual')
            ->set('phoneNumber', '51234568')
            ->call('placeOrder')
            ->assertHasNoErrors();
        $this->assertSame('telekomtl', $this->sends()->sole()[0]);
        $this->assertFalse(SimOrder::latest('id')->first()->api_supplier_forced);
    }

    public function test_the_package_form_explains_how_to_fill_in_each_supplier(): void
    {
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('create')
            ->assertSee(__('sim_guide.title'))
            ->assertSee(__('sim_guide.mistake_1'))
            ->assertSee(__('sim_guide.panel_4'))
            ->assertSee(__('sim_guide.panel_5'))                     // telekomtl: TURKTELEKOM is typed eSIM
            ->assertSee(__('messages.sim_api_section'))              // both suppliers, not «(kontor86)» only
            ->assertSee(__('messages.sim_api_kontor_hint'))
            ->assertSee(__('messages.sim_api_telekomtl_hint'))
            ->assertSee('HIZLI FİZİKSEL 50 GB · 30 gün')   // examples come from the supplier lists
            ->assertSee('2285493')
            ->assertSee('OnlineKart eSIM TT 10 GB · 30 gün')
            ->assertSeeHtml('<details class="group rounded-2xl border border-sky-200 bg-sky-50/60"  open >'); // open for a new package
        app()->setLocale('tr');
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('create')
            ->assertSee(__('sim_guide.title'))
            ->assertSee('Paketi tedarikçilere bağlama rehberi');
    }

    public function test_customers_cannot_choose_a_supplier(): void
    {
        KontorApiService::setPrimarySupplier('telekomtl');

        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectPackage', $this->fiveGb->id)
            ->assertDontSee(__('messages.sim_supplier_choice_label'))
            ->set('supplierChoice', 'kontor86') // forged request
            ->set('phoneNumber', '51234567')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $this->assertSame('telekomtl', $this->sends()->sole()[0]);
        $this->assertFalse(SimOrder::sole()->api_supplier_forced);
    }
}
