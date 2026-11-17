<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Turkey 100 GB / 30 days on a physical Tele CARD SIM, sold at 1400 ₺ (owner's price, 2026-11-17).
 * Panel: "Callback (TeleCard) FİZİKSEL Turkiye_100gb_30gun", API 26265, cost 1350 →
 * loads automatically with callback / telecard / 26265 like the other physical packages.
 * An existing package with this supplier code keeps its values (only empty API fields are filled);
 * a retired one is put back on sale. Idempotent.
 */
return new class extends Migration
{
    private const SUPPLIER_CODE = 'FİZİKSEL Turkiye_100gb_30gun';

    public function up(): void
    {
        $now = now();
        $existing = DB::table('sim_packages')->where('supplier_code', self::SUPPLIER_CODE)->orderByDesc('id')->first();

        if ($existing && $existing->deleted_at === null) {
            DB::table('sim_packages')->where('id', $existing->id)->update([
                'api_operator' => $existing->api_operator ?: 'callback',
                'api_tip' => $existing->api_tip ?: 'telecard',
                'api_code' => $existing->api_code ?: '26265',
                'updated_at' => $now,
            ]);

            return;
        }

        $row = [
            'region' => 'local',
            'sim_type' => 'physical',
            'network_name' => 'Tele CARD',
            'data_gb' => 100,
            'validity_days' => 30,
            'price' => 1400,
            'name_ar' => 'باقة تركيا 100 جيجا - 30 يوم (فيزيائية)',
            'name_tr' => 'Türkiye 100 GB - 30 Gün (Fiziksel)',
            'description_ar' => '100 جيجا إنترنت صالحة لمدة 30 يوماً، تُحمَّل على شريحة فيزيائية.',
            'description_tr' => '30 gün geçerli 100 GB internet, fiziksel SIM karta yüklenir.',
            'supplier_code' => self::SUPPLIER_CODE,
            'api_operator' => 'callback',
            'api_tip' => 'telecard',
            'api_code' => '26265',
            'is_active' => true,
            'sort_order' => (int) DB::table('sim_packages')->max('sort_order') + 1,
            'deleted_at' => null,
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('sim_packages')->where('id', $existing->id)->update($row);
        } else {
            DB::table('sim_packages')->insert($row + ['created_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('sim_packages')
            ->where('supplier_code', self::SUPPLIER_CODE)
            ->whereNull('deleted_at')
            ->update(['is_active' => false, 'deleted_at' => now(), 'updated_at' => now()]);
    }
};
