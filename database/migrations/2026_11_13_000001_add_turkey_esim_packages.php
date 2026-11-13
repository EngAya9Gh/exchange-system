<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Turkey E-SİM packages from the supplier panel (Callback → Tele CARD tile), at the panel's prices:
 * 5 GB 350 · 10 GB 400 · 20 GB 550 · 50 GB/30 days 900 · 50 GB/180 days 1200.
 * The link reaches the customer by SMS on the Turkish number entered with the order.
 *
 * They load automatically: «Kontor Fiyat Ayarları» → Callback lists them as
 * "Callback (TeleCard) E-SİM Turkiye_*" → operator=callback, tip=telecard, kontor=<API column>
 * (5gb 35240, 10gb 35250, 20gb 35270, 50gb_30gun 35290, 50gb_180gun 35310 — read from the panel 2026-11-13).
 *
 * 5 GB and 20 GB existed before and were retired (soft-deleted) on 2026-11-05: they are restored.
 * A package an admin already re-added by hand keeps its values; only its empty API fields are filled.
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $sortBase = (int) DB::table('sim_packages')->max('sort_order');

        $howTo = [
            'ar' => 'يصل رابط التثبيت برسالة SMS إلى الرقم المكتوب في الطلب، افتح الرابط من الهاتف لتفعيل الباقة.',
            'tr' => 'Kurulum linki talepte yazılan numaraya SMS olarak gelir; linki telefonda açarak paketi yükleyin.',
        ];

        $catalogue = [
            ['code' => 'E-SİM Turkiye_5gb_30gun', 'api' => '35240', 'gb' => 5, 'days' => 30, 'price' => 350, 'ar' => '30 يوم', 'tr' => '30 Gün', 'ar_valid' => 'لمدة 30 يوماً', 'tr_valid' => '30 gün geçerli'],
            ['code' => 'E-SİM Turkiye_10gb_30gun', 'api' => '35250', 'gb' => 10, 'days' => 30, 'price' => 400, 'ar' => '30 يوم', 'tr' => '30 Gün', 'ar_valid' => 'لمدة 30 يوماً', 'tr_valid' => '30 gün geçerli'],
            ['code' => 'E-SİM Turkiye_20gb_30gun', 'api' => '35270', 'gb' => 20, 'days' => 30, 'price' => 550, 'ar' => '30 يوم', 'tr' => '30 Gün', 'ar_valid' => 'لمدة 30 يوماً', 'tr_valid' => '30 gün geçerli'],
            ['code' => 'E-SİM Turkiye_50gb_30gun', 'api' => '35290', 'gb' => 50, 'days' => 30, 'price' => 900, 'ar' => '30 يوم', 'tr' => '30 Gün', 'ar_valid' => 'لمدة 30 يوماً', 'tr_valid' => '30 gün geçerli'],
            ['code' => 'E-SİM Turkiye_50gb_180gun', 'api' => '35310', 'gb' => 50, 'days' => 180, 'price' => 1200, 'ar' => '6 أشهر', 'tr' => '6 Ay', 'ar_valid' => 'لمدة 6 أشهر (180 يوماً)', 'tr_valid' => '6 ay (180 gün) geçerli'],
        ];

        foreach ($catalogue as $index => $item) {
            $existing = DB::table('sim_packages')
                ->where('supplier_code', $item['code'])
                ->orderByDesc('id')
                ->first();

            if ($existing && $existing->deleted_at === null) {
                // Already on sale: keep the admin's values, only complete the API key
                DB::table('sim_packages')->where('id', $existing->id)->update([
                    'api_operator' => $existing->api_operator ?: 'callback',
                    'api_tip' => $existing->api_tip ?: 'telecard',
                    'api_code' => $existing->api_code ?: $item['api'],
                    'updated_at' => $now,
                ]);
                continue;
            }

            $row = [
                'region' => 'local',
                'sim_type' => 'esim',
                'network_name' => 'Tele CARD',
                'data_gb' => $item['gb'],
                'validity_days' => $item['days'],
                'price' => $item['price'],
                'name_ar' => "باقة تركيا {$item['gb']} جيجا - {$item['ar']} (eSIM)",
                'name_tr' => "Türkiye {$item['gb']} GB - {$item['tr']} (E-SIM)",
                'description_ar' => "{$item['gb']} جيجا إنترنت صالحة {$item['ar_valid']} على eSIM. " . $howTo['ar'],
                'description_tr' => "{$item['tr_valid']} {$item['gb']} GB internet, E-SIM. " . $howTo['tr'],
                'supplier_code' => $item['code'],
                'api_operator' => 'callback',
                'api_tip' => 'telecard',
                'api_code' => $item['api'],
                'is_active' => true,
                'sort_order' => $sortBase + $index + 1,
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('sim_packages')->where('id', $existing->id)->update($row);
            } else {
                DB::table('sim_packages')->insert($row + ['created_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Retire them again (soft delete keeps past orders linked)
        DB::table('sim_packages')
            ->where('supplier_code', 'like', 'E-SİM Turkiye_%')
            ->whereNull('deleted_at')
            ->update(['is_active' => false, 'deleted_at' => now(), 'updated_at' => now()]);
    }
};
