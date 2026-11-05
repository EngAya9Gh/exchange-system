<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Final catalogue chosen on the supplier panel: physical-SIM packages only.
 *  - Retires (soft-deletes) the eSIM packages from the first price list.
 *  - Adds the missing physical 10 GB / 20 GB Turkey packages.
 *  - Fills the network name and display order of the existing packages,
 *    without touching prices or names an admin may already have edited.
 * Idempotent: safe on fresh installs and on servers that ran the earlier SIM migrations.
 */
return new class extends Migration
{
    private const RETIRED_SUPPLIER_CODES = [
        'E-SİM Turkiye_5gb_30gun',
        'E-SİM Turkiye_20gb_30gun',
        'AVRUPA_ESİM_30gun_25gb',
    ];

    public function up(): void
    {
        $now = now();

        $catalogue = [
            // ── Turkey (local) — network "Tele CARD" ──
            [
                'region' => 'local', 'network_name' => 'Tele CARD', 'sim_type' => 'physical', 'data_gb' => 5, 'validity_days' => 30, 'price' => 270,
                'name_ar' => 'باقة تركيا 5 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Türkiye 5 GB - 30 Gün (Fiziksel)',
                'description_ar' => '5 جيجا إنترنت صالحة لمدة 30 يوماً، تُحمَّل على شريحة فيزيائية.',
                'description_tr' => '30 gün geçerli 5 GB internet, fiziksel SIM karta yüklenir.',
                'supplier_code' => 'FİZİKSEL Turkiye_5gb_30gun',
            ],
            [
                'region' => 'local', 'network_name' => 'Tele CARD', 'sim_type' => 'physical', 'data_gb' => 10, 'validity_days' => 30, 'price' => 350,
                'name_ar' => 'باقة تركيا 10 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Türkiye 10 GB - 30 Gün (Fiziksel)',
                'description_ar' => '10 جيجا إنترنت لمدة شهر، تُحمَّل على شريحة فيزيائية.',
                'description_tr' => 'Fiziksel SIM\'e yüklenen 1 aylık 10 GB internet.',
                'supplier_code' => 'FİZİKSEL Turkiye_10gb_30gun',
            ],
            [
                'region' => 'local', 'network_name' => 'Tele CARD', 'sim_type' => 'physical', 'data_gb' => 20, 'validity_days' => 30, 'price' => 550,
                'name_ar' => 'باقة تركيا 20 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Türkiye 20 GB - 30 Gün (Fiziksel)',
                'description_ar' => '20 جيجا إنترنت لمدة شهر، تُحمَّل على شريحة فيزيائية.',
                'description_tr' => 'Fiziksel SIM\'e yüklenen 1 aylık 20 GB internet.',
                'supplier_code' => 'FİZİKSEL Turkiye_20gb_30gun',
            ],
            [
                'region' => 'local', 'network_name' => 'Tele CARD', 'sim_type' => 'physical', 'data_gb' => 50, 'validity_days' => 30, 'price' => 900,
                'name_ar' => 'باقة تركيا 50 جيجا - شهر (فيزيائية)',
                'name_tr' => 'Türkiye 50 GB - 1 Ay (Fiziksel)',
                'description_ar' => '50 جيجا إنترنت لمدة شهر، تُحمَّل على شريحة فيزيائية.',
                'description_tr' => 'Fiziksel SIM\'e yüklenen 1 aylık 50 GB internet.',
                'supplier_code' => 'FİZİKSEL Turkiye_50gb_30gun',
            ],
            [
                'region' => 'local', 'network_name' => 'Tele CARD', 'sim_type' => 'physical', 'data_gb' => 50, 'validity_days' => 180, 'price' => 1100,
                'name_ar' => 'باقة تركيا 50 جيجا - 6 أشهر (فيزيائية)',
                'name_tr' => 'Türkiye 50 GB - 6 Ay (Fiziksel)',
                'description_ar' => '50 جيجا إنترنت صالحة لمدة 6 أشهر (180 يوماً)، تُحمَّل على شريحة فيزيائية.',
                'description_tr' => 'Fiziksel SIM\'e yüklenen 6 aylık (180 gün) 50 GB internet.',
                'supplier_code' => 'FİZİKSEL Turkiye_50gb_180gun',
            ],
            // ── Europe (international) — network "eSIM" (tile name on the supplier panel) ──
            [
                'region' => 'europe', 'network_name' => 'eSIM', 'sim_type' => 'physical', 'data_gb' => 5, 'validity_days' => 30, 'price' => 500,
                'name_ar' => 'باقة أوروبا 5 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Avrupa 5 GB - 30 Gün (Fiziksel)',
                'description_ar' => '5 جيجا إنترنت للاستخدام في أوروبا لمدة 30 يوماً، على شريحة فيزيائية.',
                'description_tr' => 'Avrupa\'da 30 gün geçerli 5 GB internet, fiziksel SIM.',
                'supplier_code' => 'AVRUPA_FİZİKSEL_30gun_5gb',
            ],
            [
                'region' => 'europe', 'network_name' => 'eSIM', 'sim_type' => 'physical', 'data_gb' => 10, 'validity_days' => 30, 'price' => 750,
                'name_ar' => 'باقة أوروبا 10 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Avrupa 10 GB - 30 Gün (Fiziksel)',
                'description_ar' => '10 جيجا إنترنت للاستخدام في أوروبا لمدة 30 يوماً، على شريحة فيزيائية.',
                'description_tr' => 'Avrupa\'da 30 gün geçerli 10 GB internet, fiziksel SIM.',
                'supplier_code' => 'AVRUPA_FİZİKSEL_30gun_10gb',
            ],
            [
                'region' => 'europe', 'network_name' => 'eSIM', 'sim_type' => 'physical', 'data_gb' => 20, 'validity_days' => 30, 'price' => 1000,
                'name_ar' => 'باقة أوروبا 20 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Avrupa 20 GB - 30 Gün (Fiziksel)',
                'description_ar' => '20 جيجا إنترنت للاستخدام في أوروبا لمدة 30 يوماً، على شريحة فيزيائية.',
                'description_tr' => 'Avrupa\'da 30 gün geçerli 20 GB internet, fiziksel SIM.',
                'supplier_code' => 'AVRUPA_FİZİKSEL_30gun_20gb',
            ],
        ];

        DB::transaction(function () use ($catalogue, $now) {
            // 1. eSIM packages are no longer sold (soft delete keeps past orders intact)
            DB::table('sim_packages')
                ->whereIn('supplier_code', self::RETIRED_SUPPLIER_CODES)
                ->whereNull('deleted_at')
                ->update(['is_active' => false, 'deleted_at' => $now, 'updated_at' => $now]);

            // 2. Add missing packages; for existing ones only fill network + display order
            foreach ($catalogue as $index => $package) {
                $existing = DB::table('sim_packages')
                    ->where('supplier_code', $package['supplier_code'])
                    ->whereNull('deleted_at')
                    ->first();

                if ($existing) {
                    DB::table('sim_packages')->where('id', $existing->id)->update([
                        'network_name' => $existing->network_name ?: $package['network_name'],
                        'sort_order' => $index + 1,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('sim_packages')->insert(array_merge($package, [
                        'is_active' => true,
                        'sort_order' => $index + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
                }
            }

            // 3. Orders placed before the network column existed: copy it from their package
            DB::table('sim_orders')
                ->whereNull('network_name')
                ->whereNotNull('sim_package_id')
                ->orderBy('id')
                ->get(['id', 'sim_package_id'])
                ->each(function ($order) {
                    $network = DB::table('sim_packages')->where('id', $order->sim_package_id)->value('network_name');
                    if ($network) {
                        DB::table('sim_orders')->where('id', $order->id)->update(['network_name' => $network]);
                    }
                });
        });
    }

    public function down(): void
    {
        // Data migration: nothing to undo automatically (packages can be managed from the admin screen).
    }
};
