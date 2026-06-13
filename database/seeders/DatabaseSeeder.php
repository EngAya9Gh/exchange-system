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
        // 1. Seed Users
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
            'name' => 'وكيل افتراضي',
            'email' => 'agent@app.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'two_factor_enabled' => true,
            'phone' => '+201234567891',
            'is_active' => true,
            'language' => 'ar'
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

        // 2. Seed Commission Tiers
        \App\Models\CommissionTier::create([
            'min_amount' => 0,
            'max_amount' => 1000,
            'commission_type' => 'fixed',
            'commission_value' => 20,
            'status' => 'active'
        ]);

        \App\Models\CommissionTier::create([
            'min_amount' => 1000,
            'max_amount' => 5000,
            'commission_type' => 'percentage',
            'commission_value' => 2,
            'status' => 'active'
        ]);

        // 3. Seed Exchange Rates
        \App\Models\ExchangeRate::create([
            'from_currency' => 'TRY',
            'to_currency' => 'EGP',
            'rate' => 1.500
        ]);
        
        \App\Models\ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EGP',
            'rate' => 48.000
        ]);
    }
}
