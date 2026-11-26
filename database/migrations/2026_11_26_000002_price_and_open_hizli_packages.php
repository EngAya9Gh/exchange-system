<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Owner's prices for the «⚡ سريع · Hızlı» (kontor86 HIZLI FİZİKSEL) packages, 2026-11-26, and they go on sale:
 * 5 GB 150 · 10 GB 220 · 20 GB 310 · 50 GB 570 · 100 GB 1070 ₺ (costs 140 / 190 / 290 / 555 / 1030).
 * The teacher deleted his own 50 GB card, so a size with no fast package left is added again. Idempotent.
 */
return new class extends Migration
{
    private const PACKAGES = [
        5 => ['code' => '7036449', 'price' => 150],
        10 => ['code' => '6987918', 'price' => 220],
        20 => ['code' => '2652690', 'price' => 310],
        50 => ['code' => '2285493', 'price' => 570],
        100 => ['code' => '2205285', 'price' => 1070],
    ];

    public function up(): void
    {
        $sort = (int) DB::table('sim_packages')->max('sort_order');

        foreach (self::PACKAGES as $gb => $package) {
            $fast = DB::table('sim_packages')
                ->whereNull('deleted_at')
                ->where('is_fast', true)
                ->where('region', 'local')
                ->where('sim_type', 'physical')
                ->where('data_gb', $gb)
                ->where('validity_days', 30);

            if ($fast->exists()) {
                $fast->update(['price' => $package['price'], 'is_active' => true, 'updated_at' => now()]);
                continue;
            }

            DB::table('sim_packages')->insert([
                'region' => 'local',
                'sim_type' => 'physical',
                'network_name' => 'Tele CARD',
                'is_fast' => true,
                'data_gb' => $gb,
                'validity_days' => 30,
                'price' => $package['price'],
                'name_ar' => "باقة تركيا سريعة {$gb} جيجا - 30 يوم (فيزيائية)",
                'name_tr' => "Türkiye Hızlı {$gb} GB - 30 Gün (Fiziksel)",
                'supplier_code' => "HIZLI FİZİKSEL {$gb}GB",
                'api_operator' => 'Callback',
                'api_tip' => 'TeleCard',
                'api_code' => $package['code'],
                'is_active' => true,
                'sort_order' => ++$sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Prices stay (they can be changed in «إدارة الباقات والأسعار»).
    }
};
