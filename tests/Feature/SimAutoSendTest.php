<?php

namespace Tests\Feature;

use App\Livewire\Sim\BuyPackage;
use App\Livewire\Sim\Orders;
use App\Livewire\Sim\PackagesManager;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Models\User;
use App\Notifications\SimOrderStatusNotification;
use App\Services\SimOrderService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Automatic loading of SIM packages through kontor86 (tl_servis / tl_kontrol). */
class SimAutoSendTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected SimPackage $fiveGb;

    /** What the fake supplier answers next (string body, or 'TIMEOUT') */
    protected string $sendReply = 'OK|1|Talebiniz İşleme Alınmıştır.|220.00';
    protected string $checkReply = '';

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
            'services.kontor.dealer_code' => '5392065499',
            'services.kontor.password' => 'secret',
            'whatsapp.providers.wakeel.api_key' => 'test-key',
        ]);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();
            if (str_contains($url, 'tl_servis.php')) {
                if ($this->sendReply === 'TIMEOUT') {
                    throw new ConnectionException('timeout');
                }

                return Http::response($this->sendReply);
            }
            if (str_contains($url, 'tl_kontrol.php')) {
                return Http::response($this->checkReply);
            }
            if (str_contains($url, 'bakiye_kontrol.php')) {
                return Http::response('OK|2000.00|0.00');
            }
            if (str_contains($url, 'provider.wakeel.cc')) {
                return Http::response(['status' => true]);
            }

            return Http::response('unexpected', 500);
        });

        $this->fiveGb = SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_5gb_30gun')->firstOrFail();
    }

    protected function sentTo(string $endpoint): \Illuminate\Support\Collection
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), $endpoint))->map(fn ($pair) => $pair[0]);
    }

    protected function groupMessage(): string
    {
        return (string) $this->sentTo('provider.wakeel.cc')->last()['message'];
    }

    public function test_api_codes_from_the_panel_are_filled_for_the_physical_turkey_packages(): void
    {
        $codes = SimPackage::where('region', 'local')->where('sim_type', 'physical')->ordered()->pluck('api_code', 'supplier_code')->all();
        $this->assertSame([
            'FİZİKSEL Turkiye_5gb_30gun' => '26240',
            'FİZİKSEL Turkiye_10gb_30gun' => '26241',
            'FİZİKSEL Turkiye_20gb_30gun' => '26250',
            'FİZİKSEL Turkiye_50gb_30gun' => '26260',
            'FİZİKSEL Turkiye_50gb_180gun' => '26270',
            'FİZİKSEL Turkiye_100gb_30gun' => '26265', // added 2026-11-17 at 1400 ₺
        ], $codes);
        $this->assertEquals(1400, SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_100gb_30gun')->value('price'));

        // Verified live: operator=callback, tip=telecard → all 5 Turkey packages send automatically
        foreach (SimPackage::where('region', 'local')->where('sim_type', 'physical')->get() as $package) {
            $this->assertSame(['callback', 'telecard'], [$package->api_operator, $package->api_tip]);
            $this->assertTrue($package->isApiReady());
        }

        // Europe: the panel's own key (Callback / eSIM / 4524x, same letters), tried since 2026-11-18
        $this->assertSame([
            'AVRUPA_FİZİKSEL_30gun_5gb' => ['Callback', 'eSIM', '45240'],
            'AVRUPA_FİZİKSEL_30gun_10gb' => ['Callback', 'eSIM', '45241'],
            'AVRUPA_FİZİKSEL_30gun_20gb' => ['Callback', 'eSIM', '45242'],
        ], SimPackage::where('region', 'europe')->ordered()->get()->mapWithKeys(fn ($p) => [$p->supplier_code => [$p->api_operator, $p->api_tip, $p->api_code]])->all());
    }

    public function test_accepted_load_is_sent_with_the_panel_code_and_tracked(): void
    {
        $component = Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectPackage', $this->fiveGb->id)
            ->set('phoneNumber', '51234567')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = SimOrder::sole();
        $component->assertSee(__('messages.sim_order_sent_api', ['number' => $order->order_number]));
        $send = $this->sentTo('tl_servis.php')->sole();
        $this->assertSame('5392065499', $send['bayi_kodu']);
        $this->assertSame('callback', $send['operator']);
        $this->assertSame('telecard', $send['tip']);
        $this->assertSame('26240', $send['kontor']);
        $this->assertSame('5051234567', $send['gsmno']);
        $this->assertSame(preg_replace('/\D/', '', $order->order_number), $send['tekilnumara']);

        $this->assertSame(SimOrder::STATUS_COMPLETED, $order->status);
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);
        $this->assertEquals(220, $order->api_cost);
        $this->assertEquals(50, $order->profit());
        $this->assertEquals(730, $this->customer->fresh()->balance);
        $this->assertStringContainsString('🤖 جاري التحميل', $this->groupMessage());
    }

    public function test_refused_load_is_refunded_at_once_and_the_buyer_sees_why(): void
    {
        $this->sendReply = 'OK|3|PAKET_BULUNAMADI|0.00|';

        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectPackage', $this->fiveGb->id)
            ->set('phoneNumber', '51234567')
            ->call('placeOrder')
            ->assertHasErrors('order')
            ->assertSee(__('messages.sim_api_failed_customer', ['reason' => 'PAKET_BULUNAMADI']))
            ->assertSet('phoneNumber', '51234567'); // kept so they can fix and retry

        $order = SimOrder::sole();
        $this->assertSame(SimOrder::STATUS_REJECTED, $order->status);
        $this->assertSame(SimOrder::API_FAILED, $order->api_status);
        $this->assertSame('رفض المورد التحميل التلقائي: PAKET_BULUNAMADI', $order->admin_notes);
        $this->assertEquals(1000, $this->customer->fresh()->balance); // refunded
        Notification::assertNotSentTo($this->customer, SimOrderStatusNotification::class); // they saw it on screen
        $this->assertStringContainsString('❌ فشل التحميل التلقائي (PAKET_BULUNAMADI) وتم إرجاع المبلغ للزبون', $this->groupMessage());
    }

    public function test_low_supplier_balance_refunds_without_showing_our_balance_or_cost(): void
    {
        // Exact live answer 2026-11-10
        $this->sendReply = 'OK|3|Bu İşlem İçin Bakiyeniz Yeterli Değildir. - BAK=35.0000 AL=220.00|220.00|';

        $component = Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectPackage', $this->fiveGb->id)
            ->set('phoneNumber', '51234567')
            ->call('placeOrder')
            ->assertHasErrors('order')
            ->assertSee(__('messages.sim_api_failed_customer', ['reason' => __('messages.sim_api_supplier_unavailable')]));

        $order = SimOrder::sole();
        $this->assertEquals(1000, $this->customer->fresh()->balance); // refunded
        $this->assertSame(SimOrder::API_FAILED, $order->api_status);
        $this->assertStringContainsString('BAK=35', $order->api_message); // raw reason kept for the team

        // Never visible to the buyer: not on screen, not in the order notes, not in their orders list
        foreach (['BAK=', 'AL=', '220', 'Bakiyeniz'] as $secret) {
            $component->assertDontSee($secret);
            $this->assertStringNotContainsString($secret, (string) $order->admin_notes);
        }
        Livewire::actingAs($this->customer)->test(Orders::class)->assertDontSee('BAK=')->assertDontSee('Bakiyeniz');

        // The team is alerted in the group that the account balance is too low
        $this->assertStringContainsString('🚨 رصيد حساب kontor86 غير كافٍ لإتمام التحميل', $this->groupMessage());
    }

    public function test_public_reason_keeps_normal_supplier_reasons(): void
    {
        $this->assertSame('PAKET_BULUNAMADI', SimOrder::publicSupplierReason('PAKET_BULUNAMADI'));
        $this->assertSame('Numara hatalı', SimOrder::publicSupplierReason('Numara hatalı - AL=220.00'));
        $this->assertSame(__('messages.sim_api_supplier_unavailable'), SimOrder::publicSupplierReason('Bu İşlem İçin Bakiyeniz Yeterli Değildir.'));
    }

    public function test_no_answer_is_never_refunded_blindly(): void
    {
        foreach (['TIMEOUT', 'OK|8|Bu İşlem Daha Önce Gönderildi|0.00'] as $reply) {
            $this->sendReply = $reply;
            $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '50' . random_int(10000000, 99999999));

            $this->assertSame(SimOrder::STATUS_COMPLETED, $order->status, $reply);
            $this->assertSame(SimOrder::API_UNKNOWN, $order->api_status, $reply);
            $this->assertStringContainsString('⏳ جاري التحقق', $this->groupMessage());
        }

        $this->assertEquals(460, $this->customer->fresh()->balance); // both still charged, nothing refunded
    }

    public function test_background_check_settles_open_loads(): void
    {
        $service = app(SimOrderService::class);
        $loaded = $service->placeOrder($this->customer, $this->fiveGb->id, '5051111111');
        $this->sendReply = 'TIMEOUT';
        $cancelled = $service->placeOrder($this->customer, $this->fiveGb->id, '5052222222');
        $this->assertEquals(460, $this->customer->fresh()->balance);

        // 1st: still loading at the supplier
        $this->checkReply = '2:islemde:220.00';
        Artisan::call('sims:check-api');
        $this->assertSame(SimOrder::API_PROCESSING, $loaded->fresh()->api_status);

        // 2nd: one loaded, one cancelled → refund + the buyer is told
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(fn (Request $r) => Http::response(
            str_contains($r->url(), 'tekilnumara=' . $loaded->api_unique_id) ? '1:olumlu_islem:220.00' : '3:Numara Tele CARD Değil'
        ));
        Artisan::call('sims:check-api');

        $this->assertSame(SimOrder::API_SUCCESS, $loaded->fresh()->api_status);
        $this->assertSame(SimOrder::STATUS_COMPLETED, $loaded->fresh()->status);
        $this->assertSame(SimOrder::API_FAILED, $cancelled->fresh()->api_status);
        $this->assertSame(SimOrder::STATUS_REJECTED, $cancelled->fresh()->status);
        $this->assertSame('ألغى المورد التحميل: Numara Tele CARD Değil', $cancelled->fresh()->admin_notes);
        $this->assertEquals(730, $this->customer->fresh()->balance);
        Notification::assertSentTo($this->customer, SimOrderStatusNotification::class);

        // 3rd: settled orders are not asked about again
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake();
        Artisan::call('sims:check-api');
        Http::assertNothingSent();
    }

    public function test_inactive_product_key_falls_back_to_manual_and_stops_trying(): void
    {
        $europe = SimPackage::where('supplier_code', 'AVRUPA_FİZİKSEL_30gun_20gb')->firstOrFail(); // price 1000
        $this->sendReply = 'OK|3|Aktif Kontor CallbackeSIM45242|0.00|'; // "code not active" (live format)

        $component = Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectPackage', $europe->id)
            ->set('phoneNumber', '21684186')
            ->call('placeOrder')
            ->assertHasNoErrors() // the buyer's order goes through
            ->assertDontSee('Aktif Kontor');

        $order = SimOrder::sole();
        $component->assertSee(__('messages.sim_order_created', ['number' => $order->order_number]));
        $this->assertSame(SimOrder::STATUS_COMPLETED, $order->status); // paid, not refunded
        $this->assertNull($order->api_status);                       // → manual
        $this->assertEquals(0, $this->customer->fresh()->balance);
        $this->assertStringContainsString('⚙️ لم يقبل المورد التحميل التلقائي لهذه الباقة (Aktif Kontor CallbackeSIM45242)، والطلب بانتظار التحميل اليدوي', $this->groupMessage());

        // The package stops trying: the next order is manual straight away
        $this->assertFalse($europe->fresh()->isApiReady());
        $this->customer->update(['balance' => 1000]);
        app(SimOrderService::class)->placeOrder($this->customer, $europe->id, '5021684187');
        $this->assertCount(1, $this->sentTo('tl_servis.php'));

        // The team sees why it is manual
        Livewire::actingAs($this->admin)->test(Orders::class)->assertSee(__('messages.sim_api_fell_back_manual'));

        // Other refusals still refund (e.g. our balance)
        $this->assertTrue(SimOrder::isSupplierProductProblem('Aktif Kontor turkcell3gcep5'));
        $this->assertFalse(SimOrder::isSupplierProductProblem('Numara Tele CARD Değil'));
        $this->assertSame(__('messages.sim_api_supplier_unavailable'), SimOrder::publicSupplierReason('Aktif Kontor callbackesim45242'));
    }

    public function test_team_and_admin_messages_are_statements_not_orders(): void
    {
        $keys = ['sim_group_api_no_balance', 'sim_group_api_bad_product', 'sim_group_api_fallback_manual',
            'sim_group_cost_above_price', 'sim_supplier_not_connected', 'sim_api_fell_back_manual',
            'sim_group_auto_send_paused', 'sim_group_auto_send_paused_switched', 'sim_group_supplier_low_balance',
            'sim_group_supplier_refused', 'sim_suppliers_desc', 'sim_supplier_not_connected_of', 'kontor_desc'];
        $orders = ['اشحنوا', 'حمّلوها', 'حملوها', 'راجعوا', 'أعيدوا', 'عدّلوا', 'تأكد ', 'yükleyin', 'kontrol edin', 'Top up', 'by hand', 'Check the'];

        foreach ($keys as $key) {
            foreach (['ar', 'tr', 'en'] as $locale) {
                $text = __("messages.{$key}", [], $locale);
                foreach ($orders as $word) {
                    $this->assertStringNotContainsString($word, $text, "{$locale}.{$key}");
                }
            }
        }
    }

    public function test_europe_panel_key_migration_keeps_a_key_an_admin_typed(): void
    {
        $five = SimPackage::where('supplier_code', 'AVRUPA_FİZİKSEL_30gun_5gb')->firstOrFail();
        $ten = SimPackage::where('supplier_code', 'AVRUPA_FİZİKSEL_30gun_10gb')->firstOrFail();
        $five->update(['api_operator' => null, 'api_tip' => null, 'api_code' => null]);
        $ten->update(['api_operator' => 'X', 'api_tip' => 'Y', 'api_code' => '1']);

        (require database_path('migrations/2026_11_18_000001_set_europe_packages_to_panel_api_key.php'))->up();

        $this->assertSame(['Callback', 'eSIM', '45240'], [$five->fresh()->api_operator, $five->fresh()->api_tip, $five->fresh()->api_code]);
        $this->assertSame(['X', 'Y', '1'], [$ten->fresh()->api_operator, $ten->fresh()->api_tip, $ten->fresh()->api_code]);
    }

    public function test_europe_reset_keeps_a_key_an_admin_typed(): void
    {
        $five = SimPackage::where('supplier_code', 'AVRUPA_FİZİKSEL_30gun_5gb')->firstOrFail();
        $ten = SimPackage::where('supplier_code', 'AVRUPA_FİZİKSEL_30gun_10gb')->firstOrFail();
        $five->update(['api_operator' => 'callback', 'api_tip' => 'esim', 'api_code' => '45240']); // ours → cleared
        $ten->update(['api_operator' => 'callback', 'api_tip' => 'avrupa', 'api_code' => '45241']); // admin's → kept

        (require database_path('migrations/2026_11_17_000001_make_europe_packages_manual_until_api_tip_confirmed.php'))->up();

        $this->assertFalse($five->fresh()->isApiReady());
        $this->assertSame('avrupa', $ten->fresh()->api_tip);
    }

    public function test_manual_packages_and_missing_credentials_never_call_the_supplier(): void
    {
        // A package without an API key stays manual
        $manual = $this->fiveGb->replicate()->fill(['api_operator' => null, 'api_tip' => null, 'api_code' => null, 'supplier_code' => 'MANUAL_TEST', 'badge' => null]);
        $manual->save();
        $order = app(SimOrderService::class)->placeOrder($this->customer, $manual->id, '5051234567');
        $this->assertNull($order->api_status);

        config(['services.kontor.password' => null]);
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234568');
        $this->assertNull($order->api_status);

        $this->assertCount(0, $this->sentTo('tl_servis.php'));
        $this->assertStringNotContainsString('🤖', $this->groupMessage()); // manual message unchanged
    }

    public function test_team_sees_status_cost_profit_and_can_check_now(): void
    {
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->checkReply = '1:olumlu_islem:220.00';

        Livewire::actingAs($this->admin)->test(Orders::class)
            ->assertSee(__('messages.sim_api_state_processing'))
            ->assertSee(__('messages.sim_api_cost'))->assertSee('220 ₺')
            ->assertSee(__('messages.sim_api_profit'))->assertSee('50 ₺')
            ->call('checkSupplier', $order->id)
            ->assertSee(__('messages.sim_api_state_success'))
            ->assertDontSee(__('messages.sim_api_check_now'));

        $this->assertSame(SimOrder::API_SUCCESS, $order->fresh()->api_status);

        // Customers see none of it and cannot trigger checks
        Livewire::actingAs($this->customer)->test(Orders::class)
            ->assertDontSee(__('messages.sim_api_profit'))
            ->call('checkSupplier', $order->id)
            ->assertForbidden();
    }

    public function test_package_screen_saves_the_api_key_all_or_nothing(): void
    {
        $twentyGb = SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_20gb_30gun')->firstOrFail();

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->assertSee('🤖 API callback·telecard·26250')
            ->call('edit', $twentyGb->id)
            ->assertSet('apiCode', '26250')
            ->set('apiTip', '')
            ->call('save')
            ->assertHasErrors('apiCode')
            ->assertSee(__('messages.sim_api_fields_incomplete'))
            ->set('apiTip', 'ta m') // invalid characters → translated message, not a raw key
            ->call('save')
            ->assertHasErrors('apiTip')
            ->assertDontSee('validation.regex');
        $this->assertTrue($twentyGb->fresh()->isApiReady()); // nothing saved

        // Clearing all three makes it manual; spaces are trimmed, letters kept as typed (panel: Callback / TeleCard / eSIM)
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $twentyGb->id)
            ->set('apiOperator', '')->set('apiTip', '')->set('apiCode', '')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertFalse($twentyGb->fresh()->isApiReady());

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $twentyGb->id)
            ->set('apiOperator', ' Callback ')
            ->set('apiTip', 'TeleCard')
            ->set('apiCode', ' 26250 ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('🤖 API Callback·TeleCard·26250');
        $this->assertSame(['Callback', 'TeleCard', '26250'], [$twentyGb->fresh()->api_operator, $twentyGb->fresh()->api_tip, $twentyGb->fresh()->api_code]);
    }

    protected function groupMessages(): \Illuminate\Support\Collection
    {
        return $this->sentTo('provider.wakeel.cc')->map(fn ($r) => (string) $r['message'])->values();
    }

    protected function ownerNotifications(string $status): int
    {
        return Notification::sent($this->customer, SimOrderStatusNotification::class)
            ->filter(fn ($n) => $n->toArray($this->customer)['status'] === $status)
            ->count();
    }

    public function test_loaded_by_the_supplier_is_shown_to_the_buyer_and_the_group(): void
    {
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');

        // While loading: the buyer sees "loading" and the list refreshes itself
        Livewire::actingAs($this->customer)->test(Orders::class)
            ->assertSee(__('messages.sim_status_loading'))
            ->assertDontSee(__('messages.sim_status_loaded'))
            ->assertSeeHtml('wire:poll.15s');

        $this->checkReply = '1:olumlu_islem:220.00';
        Artisan::call('sims:check-api');

        $this->assertSame(SimOrder::API_SUCCESS, $order->fresh()->api_status);
        $this->assertSame(0, $this->ownerNotifications(SimOrder::STATUS_COMPLETED)); // paused: no "loaded" message to the buyer

        $update = $this->groupMessages()->last();
        $this->assertStringContainsString('✅ *تم تحميل الباقة تلقائياً*', $update);
        $this->assertStringContainsString('```5051234567```', $update);
        $this->assertStringContainsString($order->order_number, $update);

        Livewire::actingAs($this->customer)->test(Orders::class)
            ->assertSee(__('messages.sim_status_loaded'))
            ->assertDontSee(__('messages.sim_status_loading'))
            ->assertDontSeeHtml('wire:poll');
    }

    public function test_cancelled_by_the_supplier_tells_the_group_why(): void
    {
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->checkReply = '3:Numara Tele CARD Değil';

        Artisan::call('sims:check-api');

        $this->assertSame(SimOrder::STATUS_REJECTED, $order->fresh()->status);
        $this->assertEquals(1000, $this->customer->fresh()->balance);
        $this->assertSame(1, $this->ownerNotifications(SimOrder::STATUS_REJECTED));

        $update = $this->groupMessages()->last();
        $this->assertStringContainsString('❌ *ألغى المورد التحميل وتم إرجاع المبلغ للزبون*', $update);
        $this->assertStringContainsString('📝 السبب: Numara Tele CARD Değil', $update);
    }

    public function test_cron_and_button_at_the_same_time_apply_an_outcome_once(): void
    {
        $loaded = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051111111');
        $cancelled = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5052222222');
        $staleLoaded = SimOrder::find($loaded->id);       // both copies still say "processing"
        $staleCancelled = SimOrder::find($cancelled->id);
        $groupBefore = $this->groupMessages()->count();

        $this->checkReply = '1:olumlu_islem:220.00';
        app(SimOrderService::class)->checkSupplierStatus($loaded);
        app(SimOrderService::class)->checkSupplierStatus($staleLoaded);

        $this->checkReply = '3:Numara Tele CARD Değil';
        app(SimOrderService::class)->checkSupplierStatus($cancelled);
        app(SimOrderService::class)->checkSupplierStatus($staleCancelled);

        $this->assertSame(0, $this->ownerNotifications(SimOrder::STATUS_COMPLETED)); // paused
        $this->assertSame(1, $this->ownerNotifications(SimOrder::STATUS_REJECTED));
        $this->assertSame($groupBefore + 2, $this->groupMessages()->count()); // one ✅ + one ❌, never twice
        $this->assertEquals(730, $this->customer->fresh()->balance); // 1000 - 270 - 270 + one refund of 270
    }

    public function test_late_cancellation_after_loading_is_refunded_within_a_day(): void
    {
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->checkReply = '1:olumlu_islem:220.00';
        Artisan::call('sims:check-api');
        $this->assertSame(SimOrder::API_SUCCESS, $order->fresh()->api_status);
        $checksBefore = $this->sentTo('tl_kontrol.php')->count();

        // Re-asked only once an hour
        $this->travel(30)->minutes();
        Artisan::call('sims:check-api');
        $this->assertSame($checksBefore, $this->sentTo('tl_kontrol.php')->count());

        // Still loaded → nothing changes
        $this->travel(31)->minutes();
        Artisan::call('sims:check-api');
        $this->assertSame($checksBefore + 1, $this->sentTo('tl_kontrol.php')->count());
        $this->assertSame(SimOrder::API_SUCCESS, $order->fresh()->api_status);

        // The supplier cancels it later → refunded, buyer + group told
        $this->checkReply = '3:İade edildi';
        $this->travel(61)->minutes();
        Artisan::call('sims:check-api');
        $this->assertSame(SimOrder::STATUS_REJECTED, $order->fresh()->status);
        $this->assertEquals(1000, $this->customer->fresh()->balance);
        $this->assertStringContainsString('❌', $this->groupMessages()->last());

        // After a day loads are no longer re-asked
        $old = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5059999999');
        $this->checkReply = '1:olumlu_islem:220.00';
        Artisan::call('sims:check-api');
        $this->travel(25)->hours();
        $checksBefore = $this->sentTo('tl_kontrol.php')->count();
        Artisan::call('sims:check-api');
        $this->assertSame($checksBefore, $this->sentTo('tl_kontrol.php')->count());
        $this->assertSame(SimOrder::API_SUCCESS, $old->fresh()->api_status);
    }

    public function test_open_load_is_asked_every_run_for_an_hour_then_every_15_minutes(): void
    {
        $this->sendReply = 'TIMEOUT';
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->checkReply = ''; // supplier does not know it (yet)
        $count = fn () => $this->sentTo('tl_kontrol.php')->count();

        Artisan::call('sims:check-api');
        $this->travel(1)->minutes();
        Artisan::call('sims:check-api');
        $this->assertSame(2, $count()); // first hour: every run

        $this->travel(70)->minutes();
        Artisan::call('sims:check-api');
        $this->assertSame(3, $count());
        $this->travel(5)->minutes();
        Artisan::call('sims:check-api');
        $this->assertSame(3, $count()); // after an hour: not before 15 minutes
        $this->travel(11)->minutes();
        Artisan::call('sims:check-api');
        $this->assertSame(4, $count());

        $this->assertSame(SimOrder::API_UNKNOWN, $order->fresh()->api_status);
        $this->assertEquals(730, $this->customer->fresh()->balance); // never refunded blindly
    }

    public function test_group_warns_when_the_supplier_cost_is_above_our_price(): void
    {
        $this->sendReply = 'OK|1|Talebiniz İşleme Alınmıştır.|300.00'; // 5 GB is sold at 270
        app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');

        $this->assertStringContainsString('⚠️ تنبيه: تكلفة المورد (300 ₺) أعلى من سعر البيع (270 ₺)', $this->groupMessage());
        $this->assertStringNotContainsString('عدّلوا', $this->groupMessage());

        $this->sendReply = 'OK|1|Talebiniz İşleme Alınmıştır.|220.00';
        app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234568');
        $this->assertStringNotContainsString('⚠️', $this->groupMessage());
    }

    public function test_package_cards_show_labels_duration_and_instant_loading(): void
    {
        // Starting labels from the migration
        $this->assertSame('best_seller', SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_20gb_30gun')->value('badge'));
        $this->assertSame('best_value', SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_50gb_180gun')->value('badge'));

        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->assertSee('🔥')->assertSee('الأكثر طلباً')
            ->assertSee('💎')->assertSee('أفضل قيمة')
            ->assertSee('6 أشهر')
            ->assertSee('30 يوم')
            ->assertSee(__('messages.sim_instant_load'))
            ->assertSee('≈ 54 ₺ للجيجا') // 5 GB at 270
            ->call('selectRegion', 'europe')
            ->assertSee(__('messages.sim_instant_load')); // Europe has the panel's key (falls back to manual if refused)

        app()->setLocale('tr');
        $this->assertSame('6 Ay', SimPackage::where('validity_days', 180)->first()->durationLabel());
        $this->assertSame('30 Gün', $this->fiveGb->durationLabel());
        app()->setLocale('ar');

        // No API credentials → no "instant" promise
        config(['services.kontor.password' => null]);
        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->assertDontSee(__('messages.sim_instant_load'));
    }

    public function test_admin_sets_and_clears_a_package_label(): void
    {
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $this->fiveGb->id)
            ->assertSet('badge', '')
            ->set('badge', 'hacked')
            ->call('save')
            ->assertHasErrors('badge')
            ->set('badge', 'offer')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('عرض خاص');
        $this->assertSame('offer', $this->fiveGb->fresh()->badge);

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $this->fiveGb->id)
            ->assertSet('badge', 'offer')
            ->set('badge', '')
            ->call('save');
        $this->assertNull($this->fiveGb->fresh()->badge);
    }

    public function test_cost_is_the_first_number_not_our_remaining_balance(): void
    {
        // Exact production answers 2026-11-11: cost, then our remaining balance
        $this->sendReply = 'OK|1|Talebiniz İşleme Alınmıştır.|215.00|10545.0000';
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5021681984');

        $this->assertEquals(215, $order->api_cost);
        $this->assertSame('Talebiniz İşleme Alınmıştır.', $order->api_message);
        $this->assertEquals(55, $order->profit()); // 270 − 215
        $this->assertStringNotContainsString('⚠️', $this->groupMessage()); // no false "cost above price" alert

        // Real tl_kontrol answers: status:link:cost:number::hash (processing, then loaded)
        $this->checkReply = '2::215.00:5021681984::94c339916c';
        Artisan::call('sims:check-api');
        $this->assertSame(SimOrder::API_PROCESSING, $order->fresh()->api_status);

        $this->checkReply = '1:https%3Desimqrcode.web.appXrqSu6rktHAv4MFG0DFq:215.00:5021681984::94c339916c';
        Artisan::call('sims:check-api');
        $this->assertSame(SimOrder::API_SUCCESS, $order->fresh()->api_status);
        $this->assertEquals(215, $order->fresh()->api_cost);
    }

    public function test_cost_after_a_timeout_is_taken_from_the_check_only_when_plausible(): void
    {
        $this->sendReply = 'TIMEOUT';
        $plausible = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051111111');
        $absurd = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5052222222');

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(fn (Request $r) => Http::response(
            str_contains($r->url(), 'tekilnumara=' . $plausible->api_unique_id) ? '1:olumlu_islem:220.00' : '1:olumlu_islem:10545.00'
        ));
        Artisan::call('sims:check-api');

        $this->assertEquals(220, $plausible->fresh()->api_cost);
        $this->assertNull($absurd->fresh()->api_cost); // unknown rather than wrong
        $this->assertSame(SimOrder::API_SUCCESS, $absurd->fresh()->api_status);
    }

    public function test_misread_costs_already_saved_are_repaired(): void
    {
        $recoverable = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051111111');
        // Exactly what the old reader saved for "OK|1|Talebiniz İşleme Alınmıştır.|215.00|10545.0000"
        $recoverable->update(['api_cost' => 10545, 'api_message' => 'Talebiniz İşleme Alınmıştır.|215.00']);
        $unrecoverable = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5052222222');
        $unrecoverable->update(['api_cost' => 10545, 'api_message' => 'Talebiniz İşleme Alınmıştır.']);
        $fine = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5053333333'); // cost 220

        (require database_path('migrations/2026_11_12_000002_repair_sim_order_api_costs.php'))->up();

        $this->assertEquals(215, $recoverable->fresh()->api_cost);
        $this->assertSame('Talebiniz İşleme Alınmıştır.', $recoverable->fresh()->api_message);
        $this->assertEquals(55, $recoverable->fresh()->profit());
        $this->assertNull($unrecoverable->fresh()->api_cost);
        $this->assertEquals(220, $fine->fresh()->api_cost);
    }

    public function test_loss_is_shown_as_a_loss_not_a_minus_sign(): void
    {
        $this->sendReply = 'OK|1|Talebiniz İşleme Alınmıştır.|300.00'; // sold at 270
        app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');

        Livewire::actingAs($this->admin)->test(Orders::class)
            ->assertSee(__('messages.sim_api_loss'))
            ->assertSee(__('messages.sim_stat_loss'))
            ->assertSee('30 ₺')
            ->assertDontSee('-30 ₺')
            ->assertDontSee('>-30');
    }

    public function test_statistics_and_date_filter(): void
    {
        $service = app(SimOrderService::class);
        $twentyGb = SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_20gb_30gun')->firstOrFail();
        $this->customer->update(['balance' => 5000]);

        // Last month: one 20 GB order (550, cost 220 from the fake reply)
        $this->travelTo(now()->subMonthNoOverflow()->startOfMonth()->addDays(2));
        $old = $service->placeOrder($this->customer, $twentyGb->id, '5050000001');
        $this->travelBack();

        // Today: two 5 GB loads (270 each, cost 220) + one refused (refunded 270)
        $service->placeOrder($this->customer, $this->fiveGb->id, '5050000002');
        $service->placeOrder($this->customer, $this->fiveGb->id, '5050000003');
        $this->sendReply = 'OK|3|PAKET_BULUNAMADI|0.00|';
        $service->placeOrder($this->customer, $this->fiveGb->id, '5050000004');

        $all = Livewire::actingAs($this->admin)->test(Orders::class);
        $all->assertViewHas('stats', fn ($s) => $s['total'] === 4 && $s['done_count'] === 3 && $s['refunded_count'] === 1
            && $s['sales'] == 1090 && $s['refunded'] == 270
            && $s['costed_count'] === 3 && $s['costed_cost'] == 660 && $s['profit'] == 430)
            ->assertSee(__('messages.sim_stat_profit'))
            ->assertSee('430 ₺')
            ->assertSee(__('messages.sim_stat_top_packages'));

        $all->set('period', 'today')
            ->assertViewHas('stats', fn ($s) => $s['total'] === 3 && $s['sales'] == 540 && $s['profit'] == 100)
            ->assertDontSee($old->order_number);

        $all->set('period', 'last_month')
            ->assertViewHas('stats', fn ($s) => $s['total'] === 1 && $s['sales'] == 550 && $s['profit'] == 330)
            ->assertSee($old->order_number);

        // Custom range (reversed dates are swapped)
        $all->set('period', 'custom')
            ->set('dateFrom', now()->toDateString())
            ->set('dateTo', now()->subDays(3)->toDateString())
            ->assertViewHas('stats', fn ($s) => $s['total'] === 3);

        $all->set('period', 'nonsense')->assertSet('period', 'all');

        // Customers see their own figures, never cost or profit
        Livewire::actingAs($this->customer)->test(Orders::class)
            ->assertSee(__('messages.sim_stat_spent'))
            ->assertDontSee(__('messages.sim_stat_profit'))
            ->assertDontSee(__('messages.sim_api_cost'))
            ->assertDontSee(__('messages.sim_stat_top_packages'));
    }

    public function test_turkey_esim_packages_are_on_sale_and_load_automatically(): void
    {
        $esims = SimPackage::where('region', 'local')->where('sim_type', 'esim')->ordered()->get();
        // Price and API code exactly as in the panel's Callback list (2026-11-13)
        $this->assertSame([
            'E-SİM Turkiye_5gb_30gun' => [350, '35240'],
            'E-SİM Turkiye_10gb_30gun' => [400, '35250'],
            'E-SİM Turkiye_20gb_30gun' => [550, '35270'],
            'E-SİM Turkiye_50gb_30gun' => [900, '35290'],
            'E-SİM Turkiye_50gb_180gun' => [1200, '35310'],
        ], $esims->mapWithKeys(fn ($p) => [$p->supplier_code => [(int) $p->price, $p->api_code]])->all());
        foreach ($esims as $package) {
            $this->assertTrue($package->is_active);
            $this->assertSame('Tele CARD', $package->network_name);
            $this->assertSame(['callback', 'telecard'], [$package->api_operator, $package->api_tip]);
            $this->assertTrue($package->isApiReady());
            $this->assertSame(1, SimPackage::withTrashed()->where('supplier_code', $package->supplier_code)->count()); // restored, not duplicated
        }
        $sixMonths = $esims->firstWhere('supplier_code', 'E-SİM Turkiye_50gb_180gun');
        $this->assertSame('باقة تركيا 50 جيجا - 6 أشهر (eSIM)', $sixMonths->name_ar);
        $this->assertSame('6 أشهر', $sixMonths->durationLabel());

        // Ordered from the buy screen: sent at once with the Turkish number that receives the link by SMS
        $tenGb = $esims->firstWhere('supplier_code', 'E-SİM Turkiye_10gb_30gun');
        $this->sendReply = 'OK|1|Talebiniz İşleme Alınmıştır.|255.00|2205.0000';
        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->call('selectRegion', 'local_esim')
            ->assertSee('باقة تركيا 10 جيجا - 30 يوم (eSIM)')
            ->call('selectPackage', $tenGb->id)
            ->assertSee(__('messages.sim_phone_label_esim'))
            ->set('phoneNumber', '0555 123 45 67')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $send = $this->sentTo('tl_servis.php')->sole();
        $this->assertSame(['callback', 'telecard', '35250', '5551234567'], [$send['operator'], $send['tip'], $send['kontor'], $send['gsmno']]);

        $order = SimOrder::sole();
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);
        $this->assertEquals(255, $order->api_cost);
        $this->assertEquals(145, $order->profit()); // 400 − 255
        $this->assertStringContainsString('🤖 جاري التحميل', $this->groupMessage());
    }

    public function test_esim_migration_keeps_a_package_an_admin_already_put_back_on_sale(): void
    {
        $fiveGb = SimPackage::where('supplier_code', 'E-SİM Turkiye_5gb_30gun')->firstOrFail();
        $fiveGb->update(['price' => 333, 'api_operator' => null, 'api_tip' => null, 'api_code' => null]);

        (require database_path('migrations/2026_11_13_000001_add_turkey_esim_packages.php'))->up();

        $this->assertEquals(333, $fiveGb->fresh()->price); // admin's price kept
        $this->assertSame(['callback', 'telecard', '35240'], [$fiveGb->fresh()->api_operator, $fiveGb->fresh()->api_tip, $fiveGb->fresh()->api_code]);
        $this->assertSame(5, SimPackage::where('sim_type', 'esim')->where('region', 'local')->count());
    }

    public function test_esim_packages_have_their_own_tab(): void
    {
        $physical = 'باقة تركيا 10 جيجا - 30 يوم (فيزيائية)';
        $esim = 'باقة تركيا 10 جيجا - 30 يوم (eSIM)';

        $component = Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->assertSet('region', 'local')
            ->assertSee(__('messages.sim_tab_local'))
            ->assertSee(__('messages.sim_tab_local_esim'))
            ->assertSee(__('messages.sim_tab_europe'))
            ->assertDontSee(__('messages.sim_tab_europe_esim')) // no Europe eSIM packages → no tab
            ->assertSee($physical)
            ->assertDontSee($esim);

        $component->call('selectRegion', 'local_esim')
            ->assertSee($esim)
            ->assertDontSee($physical)
            ->assertSee(__('messages.sim_type_esim'));

        $component->call('selectRegion', 'europe')
            ->assertSee('باقة أوروبا 5 جيجا - 30 يوم (فيزيائية)')
            ->assertDontSee($esim);

        // Selecting a package opens its tab; unknown tabs are ignored
        $esimTen = SimPackage::where('supplier_code', 'E-SİM Turkiye_10gb_30gun')->firstOrFail();
        $component->call('selectPackage', $esimTen->id)->assertSet('region', 'local_esim');
        $component->call('selectRegion', 'mars')->assertSet('region', 'local_esim');

        // The admin screen lists them in their own section too
        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->assertSee(__('messages.sim_tab_local_esim'))
            ->assertSee(__('messages.sim_tab_hint_esim'));
    }

    public function test_paused_auto_send_charges_and_sends_to_the_group_only(): void
    {
        Livewire::actingAs($this->admin)->test(\App\Livewire\Sim\KontorSettings::class)
            ->call('pauseAutoSend')
            ->assertSee(__('messages.sim_auto_send_paused_flash', ['supplier' => 'kontor86']))
            ->assertSee(__('messages.sim_auto_send_resume'));

        $this->assertTrue(app(\App\Services\KontorApiService::class)->isAutoSendPaused());
        $this->assertStringContainsString('⏸️ *' . __('messages.sim_group_auto_send_paused') . '*', $this->groupMessage());
        $this->assertStringContainsString('👤 Admin', $this->groupMessage());
        $this->assertDatabaseHas('activity_logs', ['user_id' => $this->admin->id, 'action' => 'sim_auto_send_paused']);

        // The buyer is charged as usual; the order goes to the group, never to the supplier
        Livewire::actingAs($this->customer)->test(BuyPackage::class)
            ->assertDontSee(__('messages.sim_instant_load'))
            ->call('selectPackage', $this->fiveGb->id)
            ->set('phoneNumber', '51234567')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = SimOrder::sole();
        $this->assertSame(SimOrder::STATUS_COMPLETED, $order->status);
        $this->assertNull($order->api_status);
        $this->assertEquals(730, $this->customer->fresh()->balance);
        $this->assertCount(0, $this->sentTo('tl_servis.php'));
        $this->assertStringContainsString('⏸️ ' . __('messages.sim_group_api_paused'), $this->groupMessage());

        // Managers see the state on the orders screen
        Livewire::actingAs($this->admin)->test(Orders::class)
            ->assertSee(__('messages.sim_auto_send_banner'))
            ->assertSee(__('messages.sim_auto_send_paused_by', ['name' => 'Admin', 'at' => now()->format('Y-m-d H:i')]));
    }

    public function test_loads_sent_before_the_pause_are_still_followed_up(): void
    {
        $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);

        app(\App\Services\KontorApiService::class)->setAutoSendPaused(true, $this->admin);
        $this->checkReply = '1:olumlu_islem:220.00';
        Artisan::call('sims:check-api');

        $this->assertSame(SimOrder::API_SUCCESS, $order->fresh()->api_status);
    }

    public function test_resuming_sends_new_orders_again(): void
    {
        app(\App\Services\KontorApiService::class)->setAutoSendPaused(true, $this->admin);

        Livewire::actingAs($this->admin)->test(Orders::class)
            ->call('resumeAutoSend')
            ->assertSee(__('messages.sim_auto_send_resumed_flash', ['supplier' => 'kontor86']))
            ->assertDontSee(__('messages.sim_auto_send_banner'));

        $this->assertFalse(app(\App\Services\KontorApiService::class)->isAutoSendPaused());
        $this->assertStringContainsString('▶️ *' . __('messages.sim_group_auto_send_resumed') . '*', $this->groupMessage());

        app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '5051234567');
        $this->assertCount(1, $this->sentTo('tl_servis.php'));
    }

    public function test_switch_is_for_sim_managers_only_and_a_double_click_posts_once(): void
    {
        Livewire::actingAs($this->customer)->test(Orders::class)
            ->call('pauseAutoSend')
            ->assertForbidden();
        $this->assertFalse(app(\App\Services\KontorApiService::class)->isAutoSendPaused());

        $before = $this->groupMessages()->count();
        $component = Livewire::actingAs($this->admin)->test(Orders::class);
        $component->call('pauseAutoSend');
        $component->call('pauseAutoSend');
        $this->assertSame($before + 1, $this->groupMessages()->count());
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'sim_auto_send_paused')->count());
    }
}
