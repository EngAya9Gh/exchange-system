<?php

use App\Models\SimPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * «eSIM تركيا» (Tele CARD E-SİM) packages load from telekomtl's OnlineKart tile («BARKOUD ESIM»), on the
 * customer's Turkish mobile number (owner's answers 2026-11-25), while kontor86 stays paused (more expensive).
 *  1. The 50 GB · 6 months eSIM becomes 49 GB: OnlineKart sells "180 GUN * 49 GB" (owner's decision).
 *  2. Every package gets its telekomtl key when exactly one telekomtl product has the same specs
 *     (5 GB → 777, 10 → 1111, 20 → 2222, 50 → 5555, 49 · 180 days → 180180).
 * Which supplier is tried first stays the «المورد الأساسي» setting (telekomtl in production).
 * kontor86 keys are kept as backup. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // deploy.sh runs migrate before rebuilding the config cache: read the product list from its file
        config(['sim_supplier_products' => require config_path('sim_supplier_products.php')]);

        $sixMonths = DB::table('sim_packages')
            ->where('supplier_code', 'E-SİM Turkiye_50gb_180gun')
            ->whereNull('deleted_at')
            ->where('data_gb', 50)
            ->get();
        foreach ($sixMonths as $package) {
            $swap = fn (?string $text) => $text === null ? null : str_replace(['50 جيجا', '50 GB'], ['49 جيجا', '49 GB'], $text);
            DB::table('sim_packages')->where('id', $package->id)->update([
                'data_gb' => 49,
                'name_ar' => $swap($package->name_ar),
                'name_tr' => $swap($package->name_tr),
                'description_ar' => $swap($package->description_ar),
                'description_tr' => $swap($package->description_tr),
                'updated_at' => now(),
            ]);
        }

        foreach (SimPackage::all() as $package) {
            $package->linkMissingSuppliers(['telekomtl']);
        }
    }

    public function down(): void
    {
        // Links are kept (they can be changed in «إدارة الباقات والأسعار»).
    }
};
