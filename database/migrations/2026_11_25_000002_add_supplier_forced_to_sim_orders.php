<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sim_orders.api_supplier_forced: a SIM manager picked the supplier for this order at purchase time
 * (sent to that supplier only, even while it is paused, with no fallback). Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sim_orders', 'api_supplier_forced')) {
            Schema::table('sim_orders', function (Blueprint $table) {
                $table->boolean('api_supplier_forced')->default(false)->after('api_supplier');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sim_orders', 'api_supplier_forced')) {
            Schema::table('sim_orders', function (Blueprint $table) {
                $table->dropColumn('api_supplier_forced');
            });
        }
    }
};
