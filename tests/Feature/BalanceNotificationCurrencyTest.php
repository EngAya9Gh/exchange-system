<?php

namespace Tests\Feature;

use App\Livewire\Admin\BalanceManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** The customer balance is in Turkish lira: the deposit notice must never say dollar. */
class BalanceNotificationCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_notice_is_in_turkish_lira_not_dollar(): void
    {
        Role::findOrCreate('Super Admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $customer = User::factory()->create(['name' => 'محمد 101', 'balance' => 0]);

        $html = Livewire::actingAs($admin)
            ->test(BalanceManagement::class)
            ->call('openDepositModal', $customer->id)
            ->set('depositAmount', 9600)
            ->call('confirmDeposit')
            ->assertHasNoErrors()
            ->html();

        $this->assertStringContainsString('المبلغ: 9,600.00 ليرة تركية', $html);
        $this->assertStringContainsString('الرصيد بعد العملية: 9,600.00 ليرة تركية (دائن)', $html);
        $this->assertStringContainsString('9,600.00 ₺', $html);
        $this->assertStringNotContainsString('دولار', $html);
        $this->assertDoesNotMatchRegularExpression('/\d \$/', $html); // no "9,600.00 $"
    }
}
