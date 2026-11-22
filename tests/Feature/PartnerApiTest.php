<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Livewire\Public\ApiDocs;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Partner API v1 (2026-11-22): other systems order SIM packages with an account's balance. */
class PartnerApiTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected User $admin;
    protected User $partner;
    protected string $key;
    protected SimPackage $fiveGb;
    protected string $sendReply = 'OK|1|Talebiniz İşleme Alınmıştır.|215.00|12000.0000';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        app()->setLocale('ar');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('manage_users', 'web');
        Permission::findOrCreate('receive_telegram_alerts', 'web');
        Role::findOrCreate('Super Admin', 'web')->givePermissionTo('manage_users');
        Role::findOrCreate('Customer', 'web');

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole('Super Admin');
        $this->partner = User::factory()->create(['name' => 'Partner Shop', 'balance' => 1000]);
        $this->partner->assignRole('Customer');
        $this->key = $this->partner->issueApiToken();

        config([
            'services.kontor.dealer_code' => '5392065499', 'services.kontor.password' => 'secret',
            'services.telekomtl.dealer_code' => null, 'services.telekomtl.password' => null,
            'whatsapp.providers.wakeel.api_key' => 'test-key',
        ]);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();
            return match (true) {
                str_contains($url, 'tl_servis.php') => Http::response($this->sendReply),
                str_contains($url, 'tl_kontrol.php') => Http::response('1:x:215.00:5051234567::a'),
                str_contains($url, 'provider.wakeel.cc') => Http::response(['status' => true]),
                default => Http::response('unexpected', 500),
            };
        });

        $this->fiveGb = SimPackage::where('supplier_code', 'FİZİKSEL Turkiye_5gb_30gun')->firstOrFail();
    }

    protected function api(string $method, string $uri, array $data = [], ?string $key = null)
    {
        return $this->json($method, '/api/v1' . $uri, $data, ['Authorization' => 'Bearer ' . ($key ?? $this->key)]);
    }

    protected function order(array $overrides = [])
    {
        return $this->api('POST', '/orders', $overrides + ['package_id' => $this->fiveGb->id, 'phone' => '5051234567', 'reference' => 'ORDER-1001']);
    }

    protected function sends(): int
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'tl_servis.php'))->count();
    }

    public function test_requests_need_a_valid_active_key(): void
    {
        $this->getJson('/api/v1/balance')->assertStatus(401)->assertJsonPath('error.code', 'unauthorized');
        $this->api('GET', '/balance', [], 'vc_wrong')->assertStatus(401);

        // X-Api-Key works too
        $this->getJson('/api/v1/balance', ['X-Api-Key' => $this->key])->assertOk();

        $this->partner->update(['api_enabled' => false]);
        $this->api('GET', '/balance')->assertStatus(401);
        $this->partner->update(['api_enabled' => true, 'is_active' => false]);
        $this->api('GET', '/balance')->assertStatus(401);
    }

    public function test_ip_whitelist(): void
    {
        $this->partner->update(['api_allowed_ips' => "203.0.113.10"]);
        $this->api('GET', '/balance')->assertStatus(403)->assertJsonPath('error.code', 'ip_not_allowed');

        $this->partner->update(['api_allowed_ips' => "203.0.113.10\n127.0.0.1"]);
        $this->api('GET', '/balance')->assertOk();
    }

    public function test_balance(): void
    {
        $this->partner->update(['balance_limit' => 200]);

        $this->api('GET', '/balance')->assertOk()->assertExactJson([
            'ok' => true,
            'balance' => ['balance' => 1000, 'credit_limit' => 200, 'unlimited' => false, 'available' => 1200, 'currency' => 'TRY'],
        ]);
        $this->assertNotNull($this->partner->fresh()->api_last_used_at);
    }

    public function test_packages_lists_only_what_is_on_sale(): void
    {
        $response = $this->api('GET', '/packages')->assertOk();
        $packages = collect($response->json('packages'));

        $this->assertSame(SimPackage::active()->count(), $packages->count());
        $this->assertFalse($packages->contains('id', SimPackage::where('is_active', false)->value('id')));
        $five = $packages->firstWhere('id', $this->fiveGb->id);
        $this->assertSame(['turkey', 'physical', 'Tele CARD', 5, 30, 270, 'TRY', '50XXXXXXXX'],
            [$five['region'], $five['type'], $five['network'], $five['data_gb'], $five['validity_days'], $five['price'], $five['currency'], $five['phone_format']]);
        $this->assertArrayNotHasKey('api_code', $five); // no supplier details
    }

    public function test_order_is_charged_sent_and_never_charged_twice(): void
    {
        $this->order()->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('order.reference', 'ORDER-1001')
            ->assertJsonPath('order.status', 'pending')
            ->assertJsonPath('order.phone', '5051234567')
            ->assertJsonPath('order.charged', 270)
            ->assertJsonPath('balance.balance', 730);

        $order = SimOrder::sole();
        $this->assertSame('ORDER-1001', $order->client_reference);
        $this->assertSame($this->partner->id, $order->user_id);
        $this->assertSame(1, $this->sends());
        $this->assertStringContainsString('🔗 API: ORDER-1001', (string) Http::recorded(fn (Request $r) => str_contains($r->url(), 'wakeel'))->last()[0]['message']);

        // Network failure on the partner side → the same request again: same order, no second charge
        $this->order()->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('order.order_number', $order->order_number)
            ->assertJsonPath('balance.balance', 730);
        $this->assertSame(1, SimOrder::count());
        $this->assertSame(1, $this->sends());
        $this->assertEquals(730, $this->partner->fresh()->balance);

        // The same reference on another account is a different order
        $other = User::factory()->create(['balance' => 1000]);
        $this->api('POST', '/orders', ['package_id' => $this->fiveGb->id, 'phone' => '5051234567', 'reference' => 'ORDER-1001'], $other->issueApiToken())
            ->assertStatus(201);
    }

    public function test_status_by_order_number_or_reference_and_only_own_orders(): void
    {
        $number = $this->order()->json('order.order_number');

        $this->api('GET', '/orders/ORDER-1001')->assertOk()->assertJsonPath('order.order_number', $number)->assertJsonPath('order.status', 'pending');
        $this->api('GET', '/orders/' . $number)->assertOk()->assertJsonPath('order.reference', 'ORDER-1001');

        Artisan::call('sims:check-api'); // the supplier confirms the load
        $this->api('GET', '/orders/ORDER-1001')->assertJsonPath('order.status', 'success');

        $other = User::factory()->create();
        $this->api('GET', '/orders/ORDER-1001', [], $other->issueApiToken())->assertStatus(404)->assertJsonPath('error.code', 'order_not_found');
    }

    public function test_refused_order_is_failed_refunded_and_hides_supplier_details(): void
    {
        $this->sendReply = 'OK|3|Bu İşlem İçin Bakiyeniz Yeterli Değildir. - BAK=35.0000 AL=215.00|215.00|';

        $response = $this->order()->assertStatus(201)
            ->assertJsonPath('order.status', 'failed')
            ->assertJsonPath('order.charged', 0)
            ->assertJsonPath('balance.balance', 1000);

        $this->assertNotEmpty($response->json('order.failure_reason'));
        $this->assertStringNotContainsString('BAK=', $response->getContent());
        $this->assertStringNotContainsString('kontor86', $response->getContent());
        $this->assertStringNotContainsString('215', (string) $response->json('order.failure_reason'));
    }

    public function test_validation_phone_package_and_balance_errors(): void
    {
        $this->api('POST', '/orders', ['phone' => '5051234567'])->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['fields' => ['package_id', 'reference']]]);
        $this->order(['reference' => 'bad reference!'])->assertStatus(422)->assertJsonPath('error.code', 'validation_error');

        // Physical needs a 50… line; eSIM any Turkish mobile
        $this->order(['phone' => '5551234567'])->assertStatus(422)->assertJsonPath('error.code', 'invalid_phone');
        $this->order(['phone' => '+90 505 123 45 67', 'reference' => 'R-2'])->assertStatus(201)->assertJsonPath('order.phone', '5051234567');
        $this->order(['phone' => '1234 5678', 'reference' => 'R-3'])->assertStatus(201)->assertJsonPath('order.phone', '5012345678'); // last 8 digits
    }

    public function test_esim_phone_and_unavailable_package_and_low_balance(): void
    {
        $esim = SimPackage::where('supplier_code', 'E-SİM Turkiye_5gb_30gun')->firstOrFail();
        $this->order(['package_id' => $esim->id, 'phone' => '0555 123 45 67'])->assertStatus(201)->assertJsonPath('order.phone', '5551234567');
        $this->order(['package_id' => $esim->id, 'phone' => '+4915112345678', 'reference' => 'R-2'])->assertStatus(422)->assertJsonPath('error.code', 'invalid_phone');

        $inactive = SimPackage::where('is_active', false)->firstOrFail();
        $this->order(['package_id' => $inactive->id, 'reference' => 'R-3'])->assertStatus(422)->assertJsonPath('error.code', 'package_unavailable');
        $this->order(['package_id' => 999999, 'reference' => 'R-4'])->assertStatus(422)->assertJsonPath('error.code', 'package_unavailable');

        $this->partner->update(['balance' => 10]);
        $this->order(['reference' => 'R-5'])->assertStatus(422)->assertJsonPath('error.code', 'insufficient_balance');
        $this->assertSame(1, SimOrder::count());
    }

    public function test_unknown_endpoint_wrong_method_and_rate_limit_answer_json(): void
    {
        $this->api('GET', '/nothing')->assertStatus(404)->assertJsonPath('error.code', 'not_found');
        $this->api('DELETE', '/balance')->assertStatus(405)->assertJsonPath('error.code', 'method_not_allowed');

        for ($i = 0; $i < 60; $i++) {
            $this->api('GET', '/balance');
        }
        $this->api('GET', '/balance')->assertStatus(429)->assertJsonPath('error.code', 'too_many_requests');
    }

    public function test_admin_creates_replaces_and_disables_keys(): void
    {
        $shop = User::factory()->create(['name' => 'Shop', 'username' => 'shop', 'balance' => 50]);
        $shop->assignRole('Customer');

        $component = Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('editUser', $shop->id)
            ->assertSee(__('messages.api_section_title'))
            ->call('generateApiKey');
        $first = $component->get('newApiToken');
        $this->assertStringStartsWith('vc_', $first);
        $component->assertSee($first)->assertSee(__('messages.api_key_shown_once'));

        $shop->refresh();
        $this->assertTrue($shop->api_enabled);
        $this->assertNotSame($first, $shop->api_token_hash); // only the hash is stored
        $this->assertSame(hash('sha256', $first), $shop->api_token_hash);
        $this->api('GET', '/balance', [], $first)->assertOk()->assertJsonPath('balance.balance', 50);

        // A new key replaces the old one
        $second = Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('editUser', $shop->id)
            ->assertSee('vc_…' . $shop->api_token_hint)
            ->call('generateApiKey')
            ->get('newApiToken');
        $this->api('GET', '/balance', [], $first)->assertStatus(401);
        $this->api('GET', '/balance', [], $second)->assertOk();

        // IP list is validated and saved with the form
        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('editUser', $shop->id)
            ->set('api_allowed_ips', 'not-an-ip')
            ->call('saveUser')
            ->assertHasErrors('api_allowed_ips')
            ->set('api_allowed_ips', "203.0.113.10\n127.0.0.1")
            ->call('saveUser')
            ->assertHasNoErrors();
        $this->assertSame("203.0.113.10\n127.0.0.1", $shop->fresh()->api_allowed_ips);

        // Disable
        Livewire::actingAs($this->admin)->test(UserManagement::class)->call('editUser', $shop->id)->call('revokeApiKey');
        $this->assertFalse($shop->fresh()->hasApiToken());
        $this->api('GET', '/balance', [], $second)->assertStatus(401);

        // Badge in the users list for API accounts
        Livewire::actingAs($this->admin)->test(UserManagement::class)->assertSee('🔌 API');
    }

    public function test_accounts_without_username_can_be_edited(): void
    {
        $signedUp = User::factory()->create(['username' => null]);
        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('editUser', $signedUp->id)
            ->assertSet('username', '')
            ->assertSee(__('messages.api_section_title'));
    }

    public function test_docs_page_is_public_in_arabic_and_turkish(): void
    {
        Livewire::test(ApiDocs::class)
            ->assertSee('واجهة API لباقات الإنترنت')
            ->assertSee('/api/v1')
            ->assertSee('POST')
            ->assertSee('insufficient_balance')
            ->call('switchLang', 'tr')
            ->assertSee('İnternet Paketleri API')
            ->assertSee('Hızlı başlangıç');

        $this->assertTrue(\Illuminate\Support\Facades\Route::has('api.docs'));
    }
}
