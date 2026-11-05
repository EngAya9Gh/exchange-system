<?php

namespace Tests\Feature;

use App\Exceptions\SimOrderException;
use App\Livewire\Sim\BuyPackage;
use App\Livewire\Sim\Orders;
use App\Livewire\Sim\PackagesManager;
use App\Models\BalanceTransaction;
use App\Models\SimOrder;
use App\Models\SimPackage;
use App\Models\User;
use App\Notifications\NewSimOrderNotification;
use App\Notifications\SimOrderStatusNotification;
use App\Services\SimOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SimPackagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected SimOrderService $service;

    protected function setUp(): void
    {
        // Register the localized routes under /ar (mcamara reads this when the URL has no locale segment)
        putenv('ROUTING_LOCALE=ar');

        parent::setUp();

        Notification::fake();
        app()->setLocale('ar');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('Super Admin', 'web')->givePermissionTo('manage_sims');
        Role::findOrCreate('Agent', 'web');
        Role::findOrCreate('Customer', 'web');

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole('Super Admin');

        $this->customer = User::factory()->create(['name' => 'Shop', 'balance' => 1000]);
        $this->customer->assignRole('Customer');

        $this->service = app(SimOrderService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('ROUTING_LOCALE');
    }

    protected function package(int $price): SimPackage
    {
        return SimPackage::where('price', $price)->firstOrFail();
    }

    public function test_initial_price_list_is_seeded_in_arabic_and_turkish(): void
    {
        $this->assertSame(8, SimPackage::count());
        $this->assertSame(0, SimPackage::where('sim_type', 'esim')->count()); // only physical SIMs are sold
        $this->assertSame(5, SimPackage::where('region', 'local')->count());
        $this->assertSame(3, SimPackage::where('region', 'europe')->count());

        // 350 ₺ is the physical 10 GB package (FİZİKSEL Turkiye_10gb_30gun)
        $tenGb = $this->package(350);
        $this->assertSame('physical', $tenGb->sim_type);
        $this->assertSame(10, $tenGb->data_gb);
        $this->assertSame('باقة تركيا 10 جيجا - 30 يوم (فيزيائية)', $tenGb->name_ar);
        $this->assertSame('Türkiye 10 GB - 30 Gün (Fiziksel)', $tenGb->name_tr);
        $this->assertSame('FİZİKSEL Turkiye_20gb_30gun', $this->package(550)->supplier_code);

        $this->assertSame(180, $this->package(1100)->validity_days);

        // Network names as on the supplier panel
        $this->assertSame(5, SimPackage::where('region', 'local')->where('network_name', 'Tele CARD')->count());
        $this->assertSame(3, SimPackage::where('region', 'europe')->where('network_name', 'eSIM')->count());
        $this->assertSame([270, 350, 550, 900, 1100], SimPackage::where('region', 'local')->ordered()->pluck('price')->map(fn ($p) => (int) $p)->all());
        $this->assertSame([500, 750, 1000], SimPackage::where('region', 'europe')->ordered()->pluck('price')->map(fn ($p) => (int) $p)->all());
    }

    public function test_order_deducts_balance_logs_transaction_and_notifies_managers(): void
    {
        $order = $this->service->placeOrder($this->customer, $this->package(270)->id, '05321234567', 'Ahmad', 'urgent');

        $this->assertSame(SimOrder::STATUS_PENDING, $order->status);
        $this->assertStringStartsWith('SIM', $order->order_number);
        $this->assertEquals(270, $order->charged_amount);
        $this->assertEquals(730, $this->customer->fresh()->balance);

        $tx = BalanceTransaction::where('user_id', $this->customer->id)->sole();
        $this->assertSame('withdrawal', $tx->type);
        $this->assertEquals(-270, $tx->amount);
        $this->assertEquals(1000, $tx->balance_before);
        $this->assertEquals(730, $tx->balance_after);

        Notification::assertSentTo($this->admin, NewSimOrderNotification::class);
        Notification::assertNotSentTo($this->customer, NewSimOrderNotification::class);
    }

    public function test_insufficient_balance_rejects_order_without_side_effects(): void
    {
        $this->customer->update(['balance' => 100]);

        try {
            $this->service->placeOrder($this->customer, $this->package(270)->id, '05321234567');
            $this->fail('Expected SimOrderException');
        } catch (SimOrderException $e) {
            $this->assertSame(__('messages.sim_insufficient_balance'), $e->getMessage());
        }

        $this->assertSame(0, SimOrder::count());
        $this->assertEquals(100, $this->customer->fresh()->balance);
        $this->assertSame(0, BalanceTransaction::count());
    }

    public function test_balance_limit_allows_ordering_into_negative_balance(): void
    {
        $this->customer->forceFill(['balance' => 100, 'balance_limit' => 200])->save();

        $this->service->placeOrder($this->customer, $this->package(270)->id, '05321234567');

        $this->assertEquals(-170, $this->customer->fresh()->balance);
    }

    public function test_inactive_package_cannot_be_ordered(): void
    {
        $package = $this->package(270);
        $package->update(['is_active' => false]);

        $this->expectException(SimOrderException::class);
        $this->service->placeOrder($this->customer, $package->id, '05321234567');
    }

    public function test_reject_refunds_exactly_once(): void
    {
        $order = $this->service->placeOrder($this->customer, $this->package(550)->id, '05321234567');
        $this->assertEquals(450, $this->customer->fresh()->balance);

        $this->service->reject($order, $this->admin, 'Wrong number');

        $order->refresh();
        $this->assertSame(SimOrder::STATUS_REJECTED, $order->status);
        $this->assertSame('Wrong number', $order->admin_notes);
        $this->assertSame($this->admin->id, $order->processed_by);
        $this->assertEquals(1000, $this->customer->fresh()->balance);
        Notification::assertSentTo($this->customer, SimOrderStatusNotification::class);

        // A second reject (double click / two admins) must not refund again
        try {
            $this->service->reject($order, $this->admin, 'again');
            $this->fail('Expected SimOrderException');
        } catch (SimOrderException) {
        }

        $this->assertEquals(1000, $this->customer->fresh()->balance);
        $this->assertSame(1, BalanceTransaction::where('type', 'deposit')->count());
    }

    public function test_completed_order_cannot_be_rejected_afterwards(): void
    {
        $order = $this->service->placeOrder($this->customer, $this->package(270)->id, '05321234567');
        $this->service->complete($order, $this->admin, 'ICCID 8990...');

        $this->assertSame(SimOrder::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->processed_at);

        $this->expectException(SimOrderException::class);
        $this->service->reject($order, $this->admin, 'late');
    }

    public function test_super_admin_orders_are_not_charged(): void
    {
        $this->admin->update(['balance' => 0]);

        $order = $this->service->placeOrder($this->admin, $this->package(1000)->id, '5051234567');
        $this->assertEquals(0, $order->charged_amount);
        $this->assertEquals(0, $this->admin->fresh()->balance);

        $this->service->reject($order, $this->admin, 'test');
        $this->assertEquals(0, $this->admin->fresh()->balance);
        $this->assertSame(0, BalanceTransaction::count());
    }

    public function test_order_keeps_price_and_name_snapshot_after_package_edit(): void
    {
        $package = $this->package(900);
        $order = $this->service->placeOrder($this->customer, $package->id, '05321234567');

        $package->update(['price' => 950, 'name_ar' => 'اسم جديد', 'network_name' => 'Other']);

        $order->refresh();
        $this->assertEquals(900, $order->price);
        $this->assertSame('Tele CARD', $order->network_name);
        $this->assertSame('باقة تركيا 50 جيجا - شهر (فيزيائية)', $order->package_name_ar);
    }

    public function test_customer_can_order_through_the_buy_screen(): void
    {
        $package = $this->package(350);

        Livewire::actingAs($this->customer)
            ->test(BuyPackage::class)
            ->assertSee($package->name_ar)
            ->call('selectPackage', $package->id)
            ->assertSee(__('messages.sim_phone_label_physical'))
            ->assertSeeHtml('placeholder="' . __('messages.sim_phone_last8_placeholder') . '"')
            ->assertSet('phoneNumber', '')
            ->set('phoneNumber', '5123 4567') // only the last 8 digits; "50" is fixed
            ->set('customerName', 'Ali')
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertSet('selectedPackageId', null)
            ->assertSet('phoneNumber', '')
            ->assertDispatched('request-created')
            ->assertDispatched('sim-order-created');

        $order = SimOrder::sole();
        $this->assertSame('5051234567', $order->phone_number);
        $this->assertSame('Ali', $order->customer_name);

        // A pasted full number is accepted as well
        Livewire::actingAs($this->customer)
            ->test(BuyPackage::class)
            ->call('selectPackage', $this->package(270)->id)
            ->set('phoneNumber', '+90 505 987 65 43')
            ->call('placeOrder')
            ->assertHasNoErrors();
        $this->assertSame('5059876543', SimOrder::latest('id')->first()->phone_number);
        $this->assertEquals(380, $this->customer->fresh()->balance); // 1000 - 350 - 270
    }

    public function test_buy_screen_validates_phone_and_shows_balance_error(): void
    {
        $component = Livewire::actingAs($this->customer)->test(BuyPackage::class);

        $component->set('phoneNumber', '')->call('placeOrder')->assertHasErrors(['selectedPackageId', 'phoneNumber']);

        // Physical SIM: exactly the 8 digits after the fixed "50"
        $component->call('selectPackage', $this->package(270)->id);
        foreach (['50', '1234567', '123456789', 'abcdefgh', '5351234567', '505123456789'] as $bad) {
            $component->set('phoneNumber', $bad)->call('placeOrder')
                ->assertSee(__('messages.sim_invalid_last8'));
        }
        foreach (['51234567', '5123 4567', '5051234567', '0505 123 45 67', '+90 505 123 45 67'] as $good) {
            $this->assertSame('51234567', BuyPackage::lastEightDigits($good), $good);
        }

        // eSIM packages (if added later) still accept international numbers
        $esim = SimPackage::create([
            'region' => 'europe', 'sim_type' => 'esim', 'data_gb' => 5, 'validity_days' => 30, 'price' => 400,
            'name_ar' => 'eSIM test', 'name_tr' => 'eSIM test',
        ]);
        $component->call('selectPackage', $esim->id)
            ->set('phoneNumber', '+49 151 12345678')
            ->call('placeOrder')
            ->assertHasNoErrors();
        $this->assertSame('+4915112345678', SimOrder::sole()->phone_number);
        SimOrder::query()->forceDelete();

        $this->customer->update(['balance' => 0]);
        $component->call('selectPackage', $this->package(1000)->id)
            ->set('phoneNumber', '51234567')
            ->call('placeOrder')
            ->assertHasErrors('order');

        $this->assertSame(0, SimOrder::count());
    }

    public function test_europe_tab_lists_europe_packages(): void
    {
        Livewire::actingAs($this->customer)
            ->test(BuyPackage::class)
            ->assertDontSee('باقة أوروبا 20 جيجا')
            ->assertSeeHtml('<span dir="ltr">Tele CARD</span>')
            ->call('selectRegion', 'europe')
            ->assertSeeHtml('<span dir="ltr">eSIM</span>')
            ->assertDontSeeHtml('<span dir="ltr">Tele CARD</span>')
            ->assertSee('باقة أوروبا 20 جيجا - 30 يوم (فيزيائية)')
            ->assertDontSee('باقة تركيا 5 جيجا');
    }

    public function test_customer_sees_only_own_orders_and_cannot_process_them(): void
    {
        $other = User::factory()->create(['balance' => 1000]);
        $other->assignRole('Customer');

        $mine = $this->service->placeOrder($this->customer, $this->package(270)->id, '05320000001');
        $this->service->placeOrder($other, $this->package(270)->id, '05320000002');

        Livewire::actingAs($this->customer)
            ->test(Orders::class)
            ->assertSee('05320000001')
            ->assertDontSee('05320000002')
            ->call('complete', $mine->id)
            ->assertForbidden();

        $this->assertSame(SimOrder::STATUS_PENDING, $mine->fresh()->status);
    }

    public function test_manager_can_complete_and_reject_from_orders_screen(): void
    {
        $a = $this->service->placeOrder($this->customer, $this->package(270)->id, '05320000001');
        $b = $this->service->placeOrder($this->customer, $this->package(350)->id, '05320000002');

        Livewire::actingAs($this->admin)
            ->test(Orders::class)
            ->assertSet('statusFilter', 'pending')
            ->assertSee('05320000001')
            ->assertSee('Turkiye_5gb_30gun') // supplier code visible to managers
            ->call('complete', $a->id, 'done')
            ->call('reject', $b->id, '')        // reason is required
            ->assertSee(__('messages.sim_reject_reason_required'))
            ->call('reject', $b->id, 'Number not eligible');

        $this->assertSame(SimOrder::STATUS_COMPLETED, $a->fresh()->status);
        $this->assertSame(SimOrder::STATUS_REJECTED, $b->fresh()->status);
        $this->assertEquals(730, $this->customer->fresh()->balance); // 1000 - 270 (350 refunded)
    }

    public function test_packages_manager_is_admin_only_and_autofills_names(): void
    {
        Livewire::actingAs($this->customer)->test(PackagesManager::class)->assertForbidden();

        Livewire::actingAs($this->admin)
            ->test(PackagesManager::class)
            ->call('create')
            ->set('region', 'europe')
            ->set('simType', 'esim')
            ->set('dataGb', 50)
            ->set('validityDays', 30)
            ->set('price', 2500)
            ->set('networkName', '  eSIM ')
            ->call('save')
            ->assertHasNoErrors();

        $created = SimPackage::where('price', 2500)->sole();
        $this->assertSame('باقة أوروبا 50 جيجا - 30 يوم (eSIM)', $created->name_ar);
        $this->assertSame('Avrupa 50 GB - 30 Gün (E-SIM)', $created->name_tr);
        $this->assertSame('eSIM', $created->network_name);

        Livewire::actingAs($this->admin)
            ->test(PackagesManager::class)
            ->call('edit', $created->id)
            ->set('price', '')
            ->call('save')
            ->assertHasErrors(['price' => 'required']);
    }

    public function test_price_formatting(): void
    {
        $this->assertSame('1,100', SimPackage::formatPrice(1100));
        $this->assertSame('299.50', SimPackage::formatPrice('299.5'));
    }
    public function test_notification_payloads_render_and_escape_telegram_markdown(): void
    {
        $this->customer->forceFill(['name' => 'Shop_*One*', 'telegram_chat_id' => '111', 'phone' => '+905320000000'])->save();
        $this->admin->forceFill(['telegram_chat_id' => '222'])->save();

        $order = $this->service->placeOrder($this->customer, $this->package(350)->id, '05321234567', 'Ali_Veli');

        $new = new NewSimOrderNotification($order->fresh());
        $this->assertStringContainsString('05321234567', $new->toArray($this->admin)['message']);
        $tg = $new->toTelegram($this->admin);
        $this->assertSame('222', $tg['to']);
        $this->assertStringContainsString('Shop\\_\\*One\\*', $tg['text']);
        $this->assertStringContainsString('Ali\\_Veli', $tg['text']);
        $this->assertStringContainsString('*Tele CARD*', $tg['text']);
        $this->assertSame('FİZİKSEL Turkiye_10gb_30gun', $order->package->supplier_code);

        $this->service->reject($order, $this->admin, 'Bad_number');
        $status = new SimOrderStatusNotification($order->fresh(), SimOrder::STATUS_REJECTED);
        $this->assertStringContainsString('350 ₺', $status->toArray($this->customer)['message']);
        $this->assertStringContainsString('Bad_number', $status->toWhatsApp($this->customer)['message']);
        $this->assertStringContainsString('Bad\\_number', $status->toTelegram($this->customer)['text']);

        app()->setLocale('tr');
        $this->assertSame('Paket Talebi Reddedildi', $status->toArray($this->customer)['title']);
        $completed = new SimOrderStatusNotification($order->fresh(), SimOrder::STATUS_COMPLETED);
        $this->assertSame('Paket Aktif Edildi', $completed->toArray($this->customer)['title']);
        $this->assertStringContainsString('Türkiye 10 GB - 30 Gün (Fiziksel)', $completed->toArray($this->customer)['message']);
    }

    public function test_admin_dashboard_renders_sim_tabs_with_pending_badge(): void
    {
        $this->service->placeOrder($this->customer, $this->package(270)->id, '05320000001');

        foreach (['sim_orders', 'sim_buy', 'sim_packages'] as $tab) {
            Livewire::withQueryParams(['activeTab' => $tab])
                ->actingAs($this->admin)
                ->test(\App\Livewire\Admin\AdminDashboard::class)
                ->assertOk()
                ->assertSee(__('messages.sim_manage_packages'))
                ->assertSeeHtml('sim.' . str_replace('_', '-', ['sim_orders' => 'orders', 'sim_buy' => 'buy-package', 'sim_packages' => 'packages-manager'][$tab]));
        }

        // Agents can buy but don't get the management entry
        $agent = User::factory()->create(['balance' => 500]);
        $agent->assignRole('Agent');
        Livewire::withQueryParams(['activeTab' => 'sim_buy'])
            ->actingAs($agent)
            ->test(\App\Livewire\Admin\AdminDashboard::class)
            ->assertSee(__('messages.sim_buy_package'))
            ->assertDontSee(__('messages.sim_manage_packages'));
    }

    public function test_customer_sims_page_and_apps_card_render(): void
    {
        $response = $this->actingAs($this->customer)->get(route('sims.dashboard'));
        $response->assertOk()
            ->assertSee(__('messages.sim_packages_system'))
            ->assertSee(__('messages.sim_buy_package'))
            ->assertSee(__('messages.sim_my_orders'));

        $this->actingAs($this->customer)->get(route('apps'))
            ->assertOk()
            ->assertSee(route('sims.dashboard'), false);

        $this->actingAs($this->admin)->get(route('sims.dashboard'))
            ->assertRedirectContains('activeTab=sim_orders');
    }
}
