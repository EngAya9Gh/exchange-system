<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Puts the three Türk Telekom eSIM packages added inactive on 2026-11-22 on sale (owner's decision 2026-11-24:
 * the telekomtl panel loads them normally — «Transfer Takip» shows 10 GB 199/300 and 20 GB 349/450 «Yüklendi»).
 * Idempotent.
 */
return new class extends Migration
{
    private const CODES = [
        'TURKTELEKOM_ESIM_10gb_30gun',
        'TURKTELEKOM_ESIM_20gb_30gun',
        'TURKTELEKOM_ESIM_50gb_180gun',
    ];

    public function up(): void
    {
        DB::table('sim_packages')
            ->whereIn('supplier_code', self::CODES)
            ->whereNull('deleted_at')
            ->where('is_active', false)
            ->update(['is_active' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('sim_packages')->whereIn('supplier_code', self::CODES)->update(['is_active' => false]);
    }
};
