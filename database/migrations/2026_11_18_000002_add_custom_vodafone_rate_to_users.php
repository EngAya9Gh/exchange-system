<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Special Vodafone Cash rate (TRY → EGP) per customer, without changing the account type.
 *  - custom_vodafone_rate: fixed rate for this user (null = the general rate). It never follows the general rate.
 *  - custom_vodafone_rate_base: the general rate when the special rate was set / last reviewed.
 *    When the general rate moves away from it, the admin is reminded to review the special rate.
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'custom_vodafone_rate')) {
                $table->decimal('custom_vodafone_rate', 12, 5)->nullable()->after('balance_limit');
            }
            if (!Schema::hasColumn('users', 'custom_vodafone_rate_base')) {
                $table->decimal('custom_vodafone_rate_base', 12, 5)->nullable()->after('custom_vodafone_rate');
            }
            if (!Schema::hasColumn('users', 'custom_vodafone_rate_reviewed_at')) {
                $table->timestamp('custom_vodafone_rate_reviewed_at')->nullable()->after('custom_vodafone_rate_base');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['custom_vodafone_rate_reviewed_at', 'custom_vodafone_rate_base', 'custom_vodafone_rate'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
