<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all available permissions
        $permissions = [
            'manage_users',
            'manage_commissions',
            'view_profits',
            'create_transfers',
            'view_ledger',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create initial roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $agent = Role::firstOrCreate(['name' => 'Agent']);
        $customer = Role::firstOrCreate(['name' => 'Customer']);

        // Give permissions to roles
        $superAdmin->syncPermissions($permissions); // Super Admin gets everything
        $agent->syncPermissions(['create_transfers', 'view_ledger']); // Agent gets restricted permissions
        $customer->syncPermissions([]); // Customer gets nothing specific yet

        // Migrate existing users to spatie roles based on their old string role
        $users = User::all();
        foreach ($users as $user) {
            if ($user->role === 'admin') {
                $user->assignRole('Super Admin');
            } elseif ($user->role === 'agent') {
                $user->assignRole('Agent');
            } elseif ($user->role === 'customer') {
                $user->assignRole('Customer');
            }
        }
    }
}
