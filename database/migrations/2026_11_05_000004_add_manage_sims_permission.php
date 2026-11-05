<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * `manage_sims` = fulfil/reject SIM package orders and edit packages & prices.
 * Created here (not only in the seeder) because deploy.sh never runs seeders.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('manage_sims', 'web');

        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        if ($superAdmin && !$superAdmin->hasPermissionTo($permission)) {
            $superAdmin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'manage_sims')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
