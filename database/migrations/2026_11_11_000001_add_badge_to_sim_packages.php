<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Colored label on the package card (best seller, best value, new, offer, limited time),
 * chosen per package in «إدارة الباقات والأسعار».
 * Starts with the two packages loaded most often on the supplier panel; an admin can change them.
 * Idempotent.
 */
return new class extends Migration
{
    private const STARTING_BADGES = [
        'FİZİKSEL Turkiye_20gb_30gun' => 'best_seller',
        'FİZİKSEL Turkiye_50gb_180gun' => 'best_value',
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('sim_packages', 'badge')) {
            Schema::table('sim_packages', function (Blueprint $table) {
                $table->string('badge', 30)->nullable()->after('sort_order');
            });
        }

        foreach (self::STARTING_BADGES as $supplierCode => $badge) {
            DB::table('sim_packages')
                ->where('supplier_code', $supplierCode)
                ->whereNull('badge')
                ->update(['badge' => $badge, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sim_packages', 'badge')) {
            Schema::table('sim_packages', function (Blueprint $table) {
                $table->dropColumn('badge');
            });
        }
    }
};
