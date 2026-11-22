<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Türk Telekom eSIM packages sold through telekomtl (panel tile "TURKTELEKOM-ESIM", owner's selection 2026-11-22).
 * Key from telekomtl's «Kontor Fiyat Ayarları» → "Callback (eSIM) TURKTELEKOM …" → operator Callback, tip eSIM.
 * Prices = the panel's sale price. Cost: 132 / 199 / 349 / 500 / 615 / 799 / 1650.
 *
 * Three of them have a description on the panel that contradicts their name, so they are added INACTIVE
 * until the supplier confirms what is loaded:
 *   10 GB "30 يوم" → "28 Gün, 10 Gb" · 20 GB "30 يوم" → "28 Gün, 30 Gb" · 50 GB "180 يوم" → "90 Gün, 20 Gb".
 * Existing rows (same supplier_code) are left untouched. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $catalogue = [
            // supplier code                          GB   days  price  API code   on sale
            ['TURKTELEKOM_ESIM_5gb_30gun',        5,   30,   200,  '115',     true],
            ['TURKTELEKOM_ESIM_10gb_30gun',       10,  30,   300,  '175',     false],
            ['TURKTELEKOM_ESIM_20gb_30gun',       20,  30,   450,  '330',     false],
            ['TURKTELEKOM_ESIM_30gb_30gun',       30,  30,   600,  '480',     true],
            ['TURKTELEKOM_ESIM_50gb_30gun',       50,  30,   750,  '590',     true],
            ['TURKTELEKOM_ESIM_50gb_180gun',      50,  180,  900,  '2000',    false],
            ['TURKTELEKOM_ESIM_100gb_90gun',      100, 90,   1900, '909090',  true],
        ];

        $now = now();
        $sort = (int) DB::table('sim_packages')->max('sort_order');

        foreach ($catalogue as [$code, $gb, $days, $price, $apiCode, $active]) {
            if (DB::table('sim_packages')->where('supplier_code', $code)->exists()) {
                continue;
            }

            [$durationAr, $durationTr, $validAr, $validTr] = match ($days) {
                90 => ['3 أشهر', '3 Ay', 'لمدة 3 أشهر (90 يوماً)', '3 ay (90 gün) geçerli'],
                180 => ['6 أشهر', '6 Ay', 'لمدة 6 أشهر (180 يوماً)', '6 ay (180 gün) geçerli'],
                default => ["{$days} يوم", "{$days} Gün", "لمدة {$days} يوماً", "{$days} gün geçerli"],
            };

            DB::table('sim_packages')->insert([
                'region' => 'local',
                'sim_type' => 'esim',
                'network_name' => 'Türk Telekom',
                'data_gb' => $gb,
                'validity_days' => $days,
                'price' => $price,
                'name_ar' => "باقة Türk Telekom {$gb} جيجا - {$durationAr} (eSIM)",
                'name_tr' => "Türk Telekom {$gb} GB - {$durationTr} (E-SIM)",
                'description_ar' => "{$gb} جيجا إنترنت على شبكة Türk Telekom، صالحة {$validAr}، على eSIM.",
                'description_tr' => "Türk Telekom şebekesinde {$validTr} {$gb} GB internet, E-SIM.",
                'supplier_code' => $code,
                'telekomtl_operator' => 'Callback',
                'telekomtl_tip' => 'eSIM',
                'telekomtl_code' => $apiCode,
                'preferred_supplier' => 'telekomtl',
                'is_active' => $active,
                'sort_order' => ++$sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('sim_packages')->where('supplier_code', 'like', 'TURKTELEKOM_ESIM_%')->delete();
    }
};
