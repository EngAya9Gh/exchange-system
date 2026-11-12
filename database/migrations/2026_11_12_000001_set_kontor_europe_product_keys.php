<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Europe physical packages are in kontor86's «Kontor Fiyat Ayarları» list as
 * "Callback (eSIM) AVRUPA_FİZİKSEL_30gun_*" → operator=callback, tip=esim, kontor=<API column>
 * (same `Operator (Tip) Name` format as "Callback (TeleCard)", which was verified live).
 * Only fills empty fields, so values an admin already typed are kept. Idempotent.
 */
return new class extends Migration
{
    private const API_CODES = [
        'AVRUPA_FİZİKSEL_30gun_5gb' => '45240',
        'AVRUPA_FİZİKSEL_30gun_10gb' => '45241',
        'AVRUPA_FİZİKSEL_30gun_20gb' => '45242',
    ];

    public function up(): void
    {
        foreach (self::API_CODES as $supplierCode => $apiCode) {
            $packages = DB::table('sim_packages')
                ->where('supplier_code', $supplierCode)
                ->get(['id', 'api_operator', 'api_tip', 'api_code']);

            foreach ($packages as $package) {
                DB::table('sim_packages')->where('id', $package->id)->update([
                    'api_operator' => $package->api_operator ?: 'callback',
                    'api_tip' => $package->api_tip ?: 'esim',
                    'api_code' => $package->api_code ?: $apiCode,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::API_CODES as $supplierCode => $apiCode) {
            DB::table('sim_packages')
                ->where('supplier_code', $supplierCode)
                ->where('api_operator', 'callback')
                ->where('api_tip', 'esim')
                ->where('api_code', $apiCode)
                ->update(['api_operator' => null, 'api_tip' => null, 'api_code' => null]);
        }
    }
};
