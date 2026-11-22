<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner API: other systems order SIM packages from us with an account's balance.
 *  - users.api_enabled / api_token_hash (sha256, the key itself is shown once) / api_token_hint (last 4 chars)
 *    / api_allowed_ips (optional whitelist, one per line) / api_last_used_at.
 *  - sim_orders.client_reference: the partner's own id for the order; unique per account so a resent request
 *    never charges twice.
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'api_enabled')) {
                $table->boolean('api_enabled')->default(false);
            }
            if (!Schema::hasColumn('users', 'api_token_hash')) {
                $table->string('api_token_hash', 64)->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'api_token_hint')) {
                $table->string('api_token_hint', 12)->nullable();
            }
            if (!Schema::hasColumn('users', 'api_allowed_ips')) {
                $table->text('api_allowed_ips')->nullable();
            }
            if (!Schema::hasColumn('users', 'api_last_used_at')) {
                $table->timestamp('api_last_used_at')->nullable();
            }
        });

        Schema::table('sim_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sim_orders', 'client_reference')) {
                $table->string('client_reference', 64)->nullable();
                $table->unique(['user_id', 'client_reference'], 'sim_orders_user_reference_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sim_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sim_orders', 'client_reference')) {
                $table->dropUnique('sim_orders_user_reference_unique');
                $table->dropColumn('client_reference');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['api_last_used_at', 'api_allowed_ips', 'api_token_hint', 'api_token_hash', 'api_enabled'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if ($column === 'api_token_hash') {
                        $table->dropUnique(['api_token_hash']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
