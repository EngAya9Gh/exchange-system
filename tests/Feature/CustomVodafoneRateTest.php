<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Customer\NewTransferRequest;
use App\Models\ExchangeRate;
use App\Models\Transfer;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Special Vodafone Cash rate per customer (requested 2026-11-18): the account type and its screens stay the same,
 * only the TRY → EGP rate changes. The rate is fixed; when the general rate changes the admin is reminded.
 */
class CustomVodafoneRateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $omar;
    protected User $regular;
    protected ExchangeRate $tryEgp;

    protected function setUp(): void
    {
        putenv('ROUTING_LOCALE=ar');
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['status' => true], 200)]);
        Notification::fake();
        app()->setLocale('ar');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('manage_users', 'web');
        Permission::findOrCreate('receive_telegram_alerts', 'web');
        Role::findOrCreate('Super Admin', 'web')->givePermissionTo('manage_users');
        Role::findOrCreate('Agent', 'web');
        Role::findOrCreate('Customer', 'web');

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole('Super Admin');
        $this->omar = User::factory()->create(['name' => 'عمر', 'username' => 'omar', 'balance' => 5000]);
        $this->omar->assignRole('Customer');
        $this->regular = User::factory()->create(['name' => 'Shop', 'username' => 'shop', 'balance' => 5000]);
        $this->regular->assignRole('Customer');

        $this->tryEgp = ExchangeRate::updateOrCreate(['from_currency' => 'TRY', 'to_currency' => 'EGP'], ['rate' => 1.70]);
        ExchangeRate::updateOrCreate(['from_currency' => 'USD', 'to_currency' => 'EGP'], ['rate' => 50]);
    }

    protected function setOmarRate(string $rate): void
    {
        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('editUser', $this->omar->id)
            ->set('custom_vodafone_rate', $rate)
            ->call('saveUser')
            ->assertHasNoErrors();
    }

    public function test_customer_with_a_special_rate_gets_it_and_the_transfer_keeps_it(): void
    {
        $this->setOmarRate('1.66');

        $omar = $this->omar->fresh();
        $this->assertEquals(1.66, $omar->custom_vodafone_rate);
        $this->assertEquals(1.70, $omar->custom_vodafone_rate_base); // general rate when it was set
        $this->assertTrue($omar->hasRole('Customer'));               // account type unchanged

        Livewire::actingAs($omar)->test(NewTransferRequest::class)
            ->set('amount', 100)
            ->assertSet('exchange_rate', 1.66)
            ->assertSet('received_amount', 166.0)
            ->set('recipient_name', 'Ali')
            ->set('recipient_phone', '01000000000')
            ->call('submitRequest')
            ->assertHasNoErrors();

        $transfer = Transfer::where('user_id', $omar->id)->sole();
        $this->assertEquals(1.66, $transfer->exchange_rate);
        $this->assertEquals(166, $transfer->received_amount);

        // Everyone else keeps the general rate; other currencies always use the general rate
        Livewire::actingAs($this->regular)->test(NewTransferRequest::class)
            ->set('amount', 100)
            ->assertSet('exchange_rate', 1.70)
            ->assertSet('received_amount', 170.0);
        Livewire::actingAs($omar)->test(NewTransferRequest::class)
            ->set('currency', 'USD')
            ->set('amount', 100)
            ->assertSet('exchange_rate', 50.0);
    }

    public function test_the_rate_from_the_form_cannot_be_forced_by_the_browser(): void
    {
        $this->setOmarRate('1.66');

        Livewire::actingAs($this->omar->fresh())->test(NewTransferRequest::class)
            ->set('amount', 100)
            ->set('exchange_rate', 9.99) // tampered client value
            ->set('recipient_name', 'Ali')
            ->set('recipient_phone', '01000000000')
            ->call('submitRequest');

        $this->assertEquals(1.66, Transfer::sole()->exchange_rate);
    }

    public function test_clearing_the_special_rate_returns_to_the_general_rate(): void
    {
        $this->setOmarRate('1.66');
        $this->setOmarRate('');

        $omar = $this->omar->fresh();
        $this->assertFalse($omar->hasCustomVodafoneRate());
        $this->assertNull($omar->custom_vodafone_rate_base);
        $this->assertEquals(1.70, app(ExchangeRateService::class)->rateForUser($omar, 'TRY', 'EGP'));
    }

    public function test_invalid_special_rate_is_refused(): void
    {
        foreach (['abc', '0', '-1'] as $bad) {
            Livewire::actingAs($this->admin)->test(UserManagement::class)
                ->call('editUser', $this->omar->id)
                ->set('custom_vodafone_rate', $bad)
                ->call('saveUser')
                ->assertHasErrors('custom_vodafone_rate');
        }
        $this->setOmarRate('1,66'); // comma decimal accepted
        $this->assertEquals(1.66, $this->omar->fresh()->custom_vodafone_rate);
    }

    public function test_user_list_shows_the_special_rate_badge(): void
    {
        $this->setOmarRate('1.66');

        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->assertSee(__('messages.custom_rate_badge'))
            ->assertSee('1.66')
            ->assertDontSee(__('messages.custom_rate_review_title', ['count' => 1]));
    }

    public function test_changing_the_general_rate_reminds_the_admin_until_reviewed(): void
    {
        $this->setOmarRate('1.66');

        // The admin lowers the general rate: Omar stays at 1.66 (fixed) and is now flagged
        Livewire::actingAs($this->admin)->test(AdminDashboard::class)
            ->set('activeTab', 'rates')
            ->set("adjustedRates.{$this->tryEgp->id}", 1.60)
            ->call('updateRate', $this->tryEgp->id)
            ->assertSee(__('messages.custom_rate_after_general_change', ['count' => 1]));

        $this->assertEquals(1.66, $this->omar->fresh()->custom_vodafone_rate);
        $this->assertEquals(1.66, app(ExchangeRateService::class)->rateForUser($this->omar->fresh(), 'TRY', 'EGP'));

        // Reminder on the dashboard and the review list in user management
        Livewire::actingAs($this->admin)->test(AdminDashboard::class)
            ->assertSee(__('messages.custom_rate_review_title', ['count' => 1]));
        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->assertSee(__('messages.custom_rate_review_title', ['count' => 1]))
            ->assertSee(__('messages.custom_rate_above_general')) // 1.66 is now above 1.60
            ->call('keepCustomRate', $this->omar->id)
            ->assertDontSee(__('messages.custom_rate_review_title', ['count' => 1]));

        $omar = $this->omar->fresh();
        $this->assertEquals(1.66, $omar->custom_vodafone_rate);       // kept as it is
        $this->assertEquals(1.60, $omar->custom_vodafone_rate_base); // reviewed on today's rate
        Livewire::actingAs($this->admin)->test(AdminDashboard::class)
            ->assertDontSee(__('messages.custom_rate_review_title', ['count' => 1]));
    }

    public function test_keep_all_and_saving_the_form_also_count_as_reviewed(): void
    {
        $this->setOmarRate('1.66');
        $this->regular->update(['custom_vodafone_rate' => 1.75, 'custom_vodafone_rate_base' => 1.70]);
        $this->tryEgp->update(['rate' => 1.65]);

        $this->assertCount(2, User::customVodafoneRatesToReview(1.65));
        Livewire::actingAs($this->admin)->test(UserManagement::class)->call('keepAllCustomRates');
        $this->assertCount(0, User::customVodafoneRatesToReview(1.65));

        $this->tryEgp->update(['rate' => 1.62]);
        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('editUser', $this->omar->id)
            ->assertSee(__('messages.custom_rate_changed_note', ['base' => '1.65', 'current' => '1.62']))
            ->call('saveUser');
        $this->assertFalse($this->omar->fresh()->customVodafoneRateNeedsReview(1.62));
    }

    public function test_users_without_permission_see_no_reminder(): void
    {
        $this->setOmarRate('1.66');
        $this->tryEgp->update(['rate' => 1.60]);

        $agent = User::factory()->create(['balance' => 100]);
        $agent->assignRole('Agent');
        Livewire::actingAs($agent)->test(AdminDashboard::class)
            ->assertDontSee(__('messages.custom_rate_review_title', ['count' => 1]));
    }

    public function test_credit_limit_and_unlimited_balance_are_saved_from_the_user_screen(): void
    {
        // Was silently dropped before 2026-11-18 (not mass assignable)
        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('editUser', $this->omar->id)
            ->set('balance_limit', 500)
            ->call('saveUser')
            ->assertHasNoErrors();
        $this->assertEquals(500, $this->omar->fresh()->balance_limit);

        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('editUser', $this->omar->id)
            ->assertSet('balance_limit', 500.0)
            ->set('has_unlimited_balance', true)
            ->call('saveUser');
        $this->assertTrue($this->omar->fresh()->has_unlimited_balance);
    }
}
