<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Regions
        $region1 = \App\Models\Region::create(['name' => 'المنطقة الشرقية', 'status' => 'active']);
        $region2 = \App\Models\Region::create(['name' => 'البوكمال', 'status' => 'active']);

        // 2. Seed Branches
        $branch1 = \App\Models\Branch::create([
            'region_id' => $region1->id,
            'name' => 'فرع الشرقية الرئيسي',
            'status' => 'active'
        ]);
        $branch2 = \App\Models\Branch::create([
            'region_id' => $region2->id,
            'name' => 'فرع البوكمال',
            'status' => 'active'
        ]);

        // 3. Seed Users
        // Admin
        User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@app.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'two_factor_enabled' => true,
            'phone' => '+201234567890',
            'is_active' => true,
            'language' => 'ar'
        ]);

        // Agent
        User::create([
            'name' => 'عميل الشرقية',
            'email' => 'agent@app.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'two_factor_enabled' => true,
            'phone' => '+201234567891',
            'is_active' => true,
            'language' => 'ar',
            'region_id' => $region1->id,
            'branch_id' => $branch1->id
        ]);

        // Customer
        User::create([
            'name' => 'زبون تجريبي',
            'email' => 'customer@app.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'two_factor_enabled' => false,
            'phone' => '+201234567892',
            'is_active' => true,
            'language' => 'ar'
        ]);

        // 4. Seed Commission Tiers
        \App\Models\CommissionTier::create([
            'region_id' => $region1->id,
            'min_amount' => 0,
            'max_amount' => 1000,
            'commission_type' => 'fixed',
            'commission_value' => 20,
            'status' => 'active'
        ]);

        \App\Models\CommissionTier::create([
            'region_id' => $region1->id,
            'min_amount' => 1000,
            'max_amount' => 5000,
            'commission_type' => 'percentage',
            'commission_value' => 2,
            'status' => 'active'
        ]);

        // 5. Seed Exchange Rates
        // Region 1 specific
        \App\Models\ExchangeRate::create([
            'region_id' => $region1->id,
            'from_currency' => 'TRY',
            'to_currency' => 'EGP',
            'rate' => 1.520
        ]);

        // Global default
        \App\Models\ExchangeRate::create([
            'region_id' => null,
            'from_currency' => 'TRY',
            'to_currency' => 'EGP',
            'rate' => 1.500
        ]);
        
        \App\Models\ExchangeRate::create([
            'region_id' => null,
            'from_currency' => 'USD',
            'to_currency' => 'EGP',
            'rate' => 48.000
        ]);
    }
}
