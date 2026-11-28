<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «🎁 العروض»: packages an admin puts on offer (and takes off again) are grouped in their own category card on
 * «شراء باقة» and «إدارة الباقات», instead of their usual category, until the offer is removed. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sim_packages', 'is_offer')) {
            Schema::table('sim_packages', function (Blueprint $table) {
                $table->boolean('is_offer')->default(false)->after('is_fast');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sim_packages', 'is_offer')) {
            Schema::table('sim_packages', function (Blueprint $table) {
                $table->dropColumn('is_offer');
            });
        }
    }
};
