<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Second SIM supplier (telekomtl, same ZNET API as kontor86).
 *  - sim_packages.telekomtl_operator / _tip / _code: the package's product key at telekomtl
 *    (api_operator / api_tip / api_code stay the kontor86 key).
 *  - sim_orders.api_supplier: which supplier the load was sent to (its status is checked there).
 *  - sim_orders.api_note: suppliers that refused first and why (e.g. kontor86 had no balance → sent to telekomtl).
 * Existing automatic orders were all sent to kontor86. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sim_packages', function (Blueprint $table) {
            foreach (['telekomtl_operator', 'telekomtl_tip', 'telekomtl_code'] as $column) {
                if (!Schema::hasColumn('sim_packages', $column)) {
                    $table->string($column, 30)->nullable();
                }
            }
        });

        Schema::table('sim_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sim_orders', 'api_supplier')) {
                $table->string('api_supplier', 20)->nullable()->after('api_status');
            }
            if (!Schema::hasColumn('sim_orders', 'api_note')) {
                $table->text('api_note')->nullable()->after('api_message');
            }
        });

        DB::table('sim_orders')
            ->whereNotNull('api_unique_id')
            ->whereNull('api_supplier')
            ->update(['api_supplier' => 'kontor86']);
    }

    public function down(): void
    {
        Schema::table('sim_orders', function (Blueprint $table) {
            foreach (['api_note', 'api_supplier'] as $column) {
                if (Schema::hasColumn('sim_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        Schema::table('sim_packages', function (Blueprint $table) {
            foreach (['telekomtl_code', 'telekomtl_tip', 'telekomtl_operator'] as $column) {
                if (Schema::hasColumn('sim_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
