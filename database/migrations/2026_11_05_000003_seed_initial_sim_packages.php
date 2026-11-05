<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Initial SIM / internet packages (wholesale price list - Nov 2026).
 * Seeded through a migration because deploy.sh only runs `migrate --force`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('sim_packages')->exists()) {
            return;
        }

        $now = now();

        $esimDescAr = 'صالحة لمدة 30 يوماً. يصل رابط الـ eSIM برسالة SMS إلى رقم الهاتف الذي تُدخله، افتح الرابط لتثبيت الشريحة على الهاتف.';
        $esimDescTr = '30 gün geçerli. Girdiğiniz telefon numarasına SMS ile eSIM linki gelir, linki açarak eSIM\'i telefona kurabilirsiniz.';

        $packages = [
            // ── Turkey (local) ──────────────────────────────────────────────
            [
                'region' => 'local', 'sim_type' => 'physical', 'data_gb' => 5, 'validity_days' => 30, 'price' => 270,
                'name_ar' => 'باقة تركيا 5 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Türkiye 5 GB - 30 Gün (Fiziksel)',
                'description_ar' => '5 جيجا إنترنت صالحة لمدة 30 يوماً، تُحمَّل على شريحة فيزيائية.',
                'description_tr' => '30 gün geçerli 5 GB internet, fiziksel SIM karta yüklenir.',
                'supplier_code' => 'FİZİKSEL Turkiye_5gb_30gun',
            ],
            [
                'region' => 'local', 'sim_type' => 'esim', 'data_gb' => 5, 'validity_days' => 30, 'price' => 350,
                'name_ar' => 'باقة تركيا 5 جيجا - 30 يوم (eSIM)',
                'name_tr' => 'Türkiye 5 GB - 30 Gün (E-SIM)',
                'description_ar' => $esimDescAr,
                'description_tr' => $esimDescTr,
                'supplier_code' => 'E-SİM Turkiye_5gb_30gun',
            ],
            [
                'region' => 'local', 'sim_type' => 'esim', 'data_gb' => 20, 'validity_days' => 30, 'price' => 550,
                'name_ar' => 'باقة تركيا 20 جيجا - 30 يوم (eSIM)',
                'name_tr' => 'Türkiye 20 GB - 30 Gün (E-SIM)',
                'description_ar' => $esimDescAr,
                'description_tr' => $esimDescTr,
                'supplier_code' => 'E-SİM Turkiye_20gb_30gun',
            ],
            [
                'region' => 'local', 'sim_type' => 'physical', 'data_gb' => 50, 'validity_days' => 30, 'price' => 900,
                'name_ar' => 'باقة تركيا 50 جيجا - شهر (فيزيائية)',
                'name_tr' => 'Türkiye 50 GB - 1 Ay (Fiziksel)',
                'description_ar' => '50 جيجا إنترنت لمدة شهر، تُحمَّل على شريحة فيزيائية.',
                'description_tr' => 'Fiziksel SIM\'e yüklenen 1 aylık 50 GB internet.',
                'supplier_code' => 'FİZİKSEL Turkiye_50gb_30gun',
            ],
            [
                'region' => 'local', 'sim_type' => 'physical', 'data_gb' => 50, 'validity_days' => 180, 'price' => 1100,
                'name_ar' => 'باقة تركيا 50 جيجا - 6 أشهر (فيزيائية)',
                'name_tr' => 'Türkiye 50 GB - 6 Ay (Fiziksel)',
                'description_ar' => '50 جيجا إنترنت صالحة لمدة 6 أشهر (180 يوماً)، تُحمَّل على شريحة فيزيائية.',
                'description_tr' => 'Fiziksel SIM\'e yüklenen 6 aylık (180 gün) 50 GB internet.',
                'supplier_code' => 'FİZİKSEL Turkiye_50gb_180gun',
            ],

            // ── Europe (international) ──────────────────────────────────────
            [
                'region' => 'europe', 'sim_type' => 'physical', 'data_gb' => 5, 'validity_days' => 30, 'price' => 500,
                'name_ar' => 'باقة أوروبا 5 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Avrupa 5 GB - 30 Gün (Fiziksel)',
                'description_ar' => '5 جيجا إنترنت للاستخدام في أوروبا لمدة 30 يوماً، على شريحة فيزيائية.',
                'description_tr' => 'Avrupa\'da 30 gün geçerli 5 GB internet, fiziksel SIM.',
                'supplier_code' => 'AVRUPA_FİZİKSEL_30gun_5gb',
            ],
            [
                'region' => 'europe', 'sim_type' => 'physical', 'data_gb' => 10, 'validity_days' => 30, 'price' => 750,
                'name_ar' => 'باقة أوروبا 10 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Avrupa 10 GB - 30 Gün (Fiziksel)',
                'description_ar' => '10 جيجا إنترنت للاستخدام في أوروبا لمدة 30 يوماً، على شريحة فيزيائية.',
                'description_tr' => 'Avrupa\'da 30 gün geçerli 10 GB internet, fiziksel SIM.',
                'supplier_code' => 'AVRUPA_FİZİKSEL_30gun_10gb',
            ],
            [
                'region' => 'europe', 'sim_type' => 'physical', 'data_gb' => 20, 'validity_days' => 30, 'price' => 1000,
                'name_ar' => 'باقة أوروبا 20 جيجا - 30 يوم (فيزيائية)',
                'name_tr' => 'Avrupa 20 GB - 30 Gün (Fiziksel)',
                'description_ar' => '20 جيجا إنترنت للاستخدام في أوروبا لمدة 30 يوماً، على شريحة فيزيائية.',
                'description_tr' => 'Avrupa\'da 30 gün geçerli 20 GB internet, fiziksel SIM.',
                'supplier_code' => 'AVRUPA_FİZİKSEL_30gun_20gb',
            ],
            [
                'region' => 'europe', 'sim_type' => 'esim', 'data_gb' => 25, 'validity_days' => 30, 'price' => 1500,
                'name_ar' => 'باقة أوروبا 25 جيجا - 30 يوم (eSIM)',
                'name_tr' => 'Avrupa 25 GB - 30 Gün (E-SIM)',
                'description_ar' => '25 جيجا إنترنت للاستخدام في أوروبا لمدة 30 يوماً على شريحة إلكترونية eSIM. يصل رابط التثبيت برسالة SMS إلى الرقم الذي تُدخله.',
                'description_tr' => 'Avrupa\'da 30 gün geçerli 25 GB internet, E-SIM. Kurulum linki girdiğiniz numaraya SMS ile gelir.',
                'supplier_code' => 'AVRUPA_ESİM_30gun_25gb',
            ],
        ];

        foreach ($packages as $index => $package) {
            DB::table('sim_packages')->insert(array_merge($package, [
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('sim_packages')->delete();
    }
};
