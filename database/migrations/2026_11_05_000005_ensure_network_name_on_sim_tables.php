<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `network_name` was added to the create-table migrations after the first SIM release
 * may already have been migrated on the server. Add the column wherever it is missing.
 * On fresh installs the column already exists and this is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sim_packages', 'sim_orders'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'network_name')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('network_name', 100)->nullable()->after('sim_type');
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty: on fresh installs the column belongs to the create-table migrations.
    }
};
