<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * telekomtl's «Kontor Fiyat Ayarları» → Callback (screenshot 2026-11-21) lists the Europe packages as
 * "Callback (TeleCard) Avrupa 5 GB / 10 GB / 20 GB" → operator Callback, tip TeleCard, kontor 1233 / 1234 / 12346
 * (cost 220 / 295 / 460, cheaper than kontor86's 260 / 350 / 470). It has no Turkey TeleCard packages.
 *  - sim_packages.preferred_supplier: tried first for this package (null = the primary supplier).
 *  - Europe packages get the telekomtl key and prefer telekomtl.
 * Only fills empty values, never what an admin set. Idempotent.
 */
return new class extends Migration
{
    private const EUROPE_KEYS = [
        'AVRUPA_FİZİKSEL_30gun_5gb' => '1233',
        'AVRUPA_FİZİKSEL_30gun_10gb' => '1234',
        'AVRUPA_FİZİKSEL_30gun_20gb' => '12346',
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('sim_packages', 'preferred_supplier')) {
            Schema::table('sim_packages', function (Blueprint $table) {
                $table->string('preferred_supplier', 20)->nullable();
            });
        }

        foreach (self::EUROPE_KEYS as $supplierCode => $apiCode) {
            $packages = DB::table('sim_packages')->where('supplier_code', $supplierCode)->get();

            foreach ($packages as $package) {
                $hasKey = filled($package->telekomtl_operator) || filled($package->telekomtl_tip) || filled($package->telekomtl_code);
                $changes = $hasKey ? [] : ['telekomtl_operator' => 'Callback', 'telekomtl_tip' => 'TeleCard', 'telekomtl_code' => $apiCode];
                if ($package->preferred_supplier === null) {
                    $changes['preferred_supplier'] = 'telekomtl';
                }
                if ($changes) {
                    DB::table('sim_packages')->where('id', $package->id)->update($changes + ['updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::EUROPE_KEYS as $supplierCode => $apiCode) {
            DB::table('sim_packages')
                ->where('supplier_code', $supplierCode)
                ->where('telekomtl_code', $apiCode)
                ->update(['telekomtl_operator' => null, 'telekomtl_tip' => null, 'telekomtl_code' => null]);
        }

        if (Schema::hasColumn('sim_packages', 'preferred_supplier')) {
            Schema::table('sim_packages', function (Blueprint $table) {
                $table->dropColumn('preferred_supplier');
            });
        }
    }
};
