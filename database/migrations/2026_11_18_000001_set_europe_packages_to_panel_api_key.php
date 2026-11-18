<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Europe packages get the exact key the supplier panel itself uses (Chrome inspector, 2026-11-18):
 *   yukle_onay('…','Callback','eSIM','45242.00','CALLBACK','AVRUPA_FİZİKSEL_30gun_20gb', …)
 * → operator "Callback", tip "eSIM", kontor 45240 / 45241 / 45242 (same letters as the panel).
 * The lowercase "callback/esim" was refused ("Aktif Kontor callbackesim45242"). If this key is refused too,
 * SimOrderService falls back: the order goes to the team as a manual load and the package stops trying.
 * Only fills packages with no key, never an admin's. Idempotent.
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
            DB::table('sim_packages')
                ->where('supplier_code', $supplierCode)
                ->whereNull('api_operator')
                ->whereNull('api_tip')
                ->whereNull('api_code')
                ->update(['api_operator' => 'Callback', 'api_tip' => 'eSIM', 'api_code' => $apiCode, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::API_CODES as $supplierCode => $apiCode) {
            DB::table('sim_packages')
                ->where('supplier_code', $supplierCode)
                ->where('api_code', $apiCode)
                ->update(['api_operator' => null, 'api_tip' => null, 'api_code' => null]);
        }
    }
};
