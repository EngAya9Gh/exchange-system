<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Europe packages back to manual (Tele CARD WhatsApp group).
 * The key callback / esim / 4524x set on 2026-11-12 was refused live on 2026-11-17:
 * "OK|3|Aktif Kontor callbackesim45242" = no active product with that key, so every Europe order failed.
 * The right operator/tip for the panel's "Callback (eSIM)" products must come from the supplier;
 * it is then entered in «إدارة الباقات والأسعار».
 * Only clears the exact values we set, never a key an admin typed. Idempotent.
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
                ->where('api_operator', 'callback')
                ->where('api_tip', 'esim')
                ->where('api_code', $apiCode)
                ->update(['api_operator' => null, 'api_tip' => null, 'api_code' => null, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Not restored: that key does not work.
    }
};
