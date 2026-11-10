<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic loading through the kontor86 API (tl_servis.php).
 *
 * Packages: operator + tip + code = the product key the API looks up
 * (e.g. turkcell + 3gcep + 5 → "turkcell3gcep5"). The code is the "API" column of
 * «Kontor Fiyat Ayarları» in the supplier panel. A package is sent automatically only
 * when all three are filled; otherwise it stays manual (WhatsApp group).
 *
 * Orders: api_status null = manual | processing | success | failed | unknown (must be checked).
 * Idempotent: safe to run on servers that already have some of these columns.
 */
return new class extends Migration
{
    /** "API" codes from «Kontor Fiyat Ayarları» → Callback (TeleCard), 2026-11-10 */
    private const API_CODES = [
        'FİZİKSEL Turkiye_5gb_30gun' => '26240',
        'FİZİKSEL Turkiye_10gb_30gun' => '26241',
        'FİZİKSEL Turkiye_20gb_30gun' => '26250',
        'FİZİKSEL Turkiye_50gb_30gun' => '26260',
        'FİZİKSEL Turkiye_50gb_180gun' => '26270',
    ];

    public function up(): void
    {
        $packageColumns = [
            'api_operator' => fn (Blueprint $t) => $t->string('api_operator', 30)->nullable(),
            'api_tip' => fn (Blueprint $t) => $t->string('api_tip', 30)->nullable(),
            'api_code' => fn (Blueprint $t) => $t->string('api_code', 30)->nullable(),
        ];
        foreach ($packageColumns as $column => $define) {
            if (!Schema::hasColumn('sim_packages', $column)) {
                Schema::table('sim_packages', fn (Blueprint $t) => $define($t));
            }
        }

        $orderColumns = [
            'api_status' => fn (Blueprint $t) => $t->string('api_status', 20)->nullable(),
            'api_unique_id' => fn (Blueprint $t) => $t->string('api_unique_id', 30)->nullable()->index(),
            'api_cost' => fn (Blueprint $t) => $t->decimal('api_cost', 15, 2)->nullable(),
            'api_message' => fn (Blueprint $t) => $t->text('api_message')->nullable(),
            'api_checked_at' => fn (Blueprint $t) => $t->timestamp('api_checked_at')->nullable(),
        ];
        foreach ($orderColumns as $column => $define) {
            if (!Schema::hasColumn('sim_orders', $column)) {
                Schema::table('sim_orders', fn (Blueprint $t) => $define($t));
            }
        }

        // Fill the known codes without overwriting anything an admin already set
        foreach (self::API_CODES as $supplierCode => $apiCode) {
            DB::table('sim_packages')
                ->where('supplier_code', $supplierCode)
                ->whereNull('api_code')
                ->update(['api_code' => $apiCode, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['api_operator', 'api_tip', 'api_code'] as $column) {
            if (Schema::hasColumn('sim_packages', $column)) {
                Schema::table('sim_packages', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
        if (Schema::hasColumn('sim_orders', 'api_unique_id')) {
            Schema::table('sim_orders', fn (Blueprint $t) => $t->dropIndex(['api_unique_id']));
        }
        foreach (['api_status', 'api_unique_id', 'api_cost', 'api_message', 'api_checked_at'] as $column) {
            if (Schema::hasColumn('sim_orders', $column)) {
                Schema::table('sim_orders', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
    }
};
