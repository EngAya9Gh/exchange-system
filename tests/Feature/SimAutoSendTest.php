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

        // Product key placeholders (the real operator/tip come from the supplier)
        $this->fiveGb = SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_5gb_30gun')->firstOrFail();
        $this->fiveGb->update(['api_operator' => 'callback', 'api_tip' => 'tam']);
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
        $codes = SimPackage::where('region', 'local')->ordered()->pluck('api_code', 'supplier_code')->all();
        $this->assertSame([
            'FİZİKSEL Turkiye_5gb_30gun' => '26240',
            'FİZİKSEL Turkiye_10gb_30gun' => '26241',
            'FİZİKSEL Turkiye_20gb_30gun' => '26250',
            'FİZİKSEL Turkiye_50gb_30gun' => '26260',
            'FİZİKSEL Turkiye_50gb_180gun' => '26270',
        ], $codes);

        // Europe is not in the API list → no code, stays manual
        $this->assertSame(0, SimPackage::where('region', 'europe')->whereNotNull('api_code')->count());
        // Only the one we completed in setUp is ready: the others still miss operator/tip
        $this->assertSame([$this->fiveGb->id], SimPackage::all()->filter->isApiReady()->pluck('id')->values()->all());
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
        $this->assertSame('tam', $send['tip']);
        $this->assertSame('26240', $send['kontor']);
        $this->assertSame('5051234567', $send['gsmno']);
        $this->assertSame(preg_replace('/\D/', '', $order->order_number), $send['tekilnumara']);

        $this->assertSame(SimOrder::STATUS_COMPLETED, $order->status);
        $this->assertSame(SimOrder::API_PROCESSING, $order->api_status);
        $this->assertEquals(220, $order->api_cost);
        $this->assertEquals(50, $order->profit());
        $this->assertEquals(730, $this->customer->fresh()->balance);
        $this->assertStringContainsString('🤖 أُرسلت تلقائياً للمورد، لا تحمّلها يدوياً', $this->groupMessage());
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

    public function test_no_answer_is_never_refunded_blindly(): void
    {
        foreach (['TIMEOUT', 'OK|8|Bu İşlem Daha Önce Gönderildi|0.00'] as $reply) {
            $this->sendReply = $reply;
            $order = app(SimOrderService::class)->placeOrder($this->customer, $this->fiveGb->id, '50' . random_int(10000000, 99999999));

            $this->assertSame(SimOrder::STATUS_COMPLETED, $order->status, $reply);
            $this->assertSame(SimOrder::API_UNKNOWN, $order->api_status, $reply);
            $this->assertStringContainsString('⏳ أُرسلت للمورد وجاري التحقق', $this->groupMessage());
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

    public function test_manual_packages_and_missing_credentials_never_call_the_supplier(): void
    {
        $tenGb = SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_10gb_30gun')->firstOrFail(); // code only
        $order = app(SimOrderService::class)->placeOrder($this->customer, $tenGb->id, '5051234567');
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
            ->assertSee(__('messages.sim_api_profit') . ' 50 ₺')
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
            ->assertSee('26250')
            ->call('edit', $twentyGb->id)
            ->assertSet('apiCode', '26250')
            ->set('apiOperator', 'Callback')
            ->call('save')
            ->assertHasErrors('apiCode')
            ->assertSee(__('messages.sim_api_fields_incomplete'))
            ->set('apiTip', 'ta m') // invalid characters → translated message, not a raw key
            ->call('save')
            ->assertHasErrors('apiTip')
            ->assertDontSee('validation.regex');
        $this->assertFalse($twentyGb->fresh()->isApiReady());

        Livewire::actingAs($this->admin)->test(PackagesManager::class)
            ->call('edit', $twentyGb->id)
            ->set('apiOperator', ' Callback ')
            ->set('apiTip', 'TAM')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('🤖 API callback·tam·26250');

        $this->assertTrue($twentyGb->fresh()->isApiReady());
        $this->assertSame(['callback', 'tam', '26250'], [$twentyGb->fresh()->api_operator, $twentyGb->fresh()->api_tip, $twentyGb->fresh()->api_code]);
    }
}
