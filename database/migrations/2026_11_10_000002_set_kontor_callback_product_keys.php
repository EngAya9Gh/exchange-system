<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * TeleCard packages are sent as operator=callback, tip=telecard, kontor=<API code>.
 * Verified live 2026-11-10: callback/telecard/26240 → "OK|3|Bu İşlem İçin Bakiyeniz Yeterli Değildir.
 * - BAK=35.0000 AL=220.00|220.00|" (product found, cost 220, only our balance was too low).
 * Only fills empty fields, so values an admin already typed are kept. Idempotent.
 */
return new class extends Migration
{
    private const SUPPLIER_CODES = [
        'FİZİKSEL Turkiye_5gb_30gun',
        'FİZİKSEL Turkiye_10gb_30gun',
        'FİZİKSEL Turkiye_20gb_30gun',
        'FİZİKSEL Turkiye_50gb_30gun',
        'FİZİKSEL Turkiye_50gb_180gun',
    ];

    public function up(): void
    {
        $packages = DB::table('sim_packages')
            ->whereIn('supplier_code', self::SUPPLIER_CODES)
            ->whereNotNull('api_code')
            ->get(['id', 'api_operator', 'api_tip']);

        foreach ($packages as $package) {
            DB::table('sim_packages')->where('id', $package->id)->update([
                'api_operator' => $package->api_operator ?: 'callback',
                'api_tip' => $package->api_tip ?: 'telecard',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('sim_packages')
            ->whereIn('supplier_code', self::SUPPLIER_CODES)
            ->where('api_operator', 'callback')
            ->where('api_tip', 'telecard')
            ->update(['api_operator' => null, 'api_tip' => null]);
    }
};
