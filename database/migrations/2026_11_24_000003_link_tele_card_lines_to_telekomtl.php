<?php

use App\Models\SimPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * kontor86 is paused because it is more expensive; Tele CARD lines are loaded from telekomtl instead
 * (owner, 2026-11-24): its Türk Telekom products load on our "50" Tele CARD lines.
 *  1. The teacher's "100 GB · 6 months" physical package is telekomtl's "TURKTELEKOM 100 GB İnternet 90 gün"
 *     (owner's screenshot): its duration is corrected to 90 days.
 *  2. Every package gets its telekomtl key when exactly one telekomtl product has the same specs
 *     (Tele CARD physical 5 GB → 115, 10 → 175, 20 → 330, 30 → 480, 50 → 590, 50·180 days → 2000,
 *     100 → 1600, 100·90 days → 909090). kontor86 keys are kept (backup when kontor86 is resumed).
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // deploy.sh runs migrate before rebuilding the config cache: read the product list from its file
        config(['sim_supplier_products' => require config_path('sim_supplier_products.php')]);

        DB::table('sim_packages')
            ->whereNull('deleted_at')
            ->where('region', 'local')->where('sim_type', 'physical')
            ->where('data_gb', 100)->where('validity_days', 180)
            ->whereNull('api_code')->whereNull('telekomtl_code')
            ->update(['validity_days' => 90, 'updated_at' => now()]);

        foreach (SimPackage::all() as $package) {
            $package->linkMissingSuppliers(['telekomtl']);
        }
    }

    public function down(): void
    {
        // Links are kept (they can be changed in «إدارة الباقات والأسعار»).
    }
};
