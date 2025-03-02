<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        // Apartment permissions
        Permission::firstOrCreate(['name' => 'view apartments']);
        Permission::firstOrCreate(['name' => 'create apartments']);
        Permission::firstOrCreate(['name' => 'edit apartments']);
        Permission::firstOrCreate(['name' => 'delete apartments']);

        // Water meter permissions
        Permission::firstOrCreate(['name' => 'view water meters']);
        Permission::firstOrCreate(['name' => 'create water meters']);
        Permission::firstOrCreate(['name' => 'edit water meters']);
        Permission::firstOrCreate(['name' => 'delete water meters']);

        // Reading permissions
        Permission::firstOrCreate(['name' => 'view readings']);
        Permission::firstOrCreate(['name' => 'submit readings']);
        Permission::firstOrCreate(['name' => 'approve readings']);
        Permission::firstOrCreate(['name' => 'reject readings']);

        // Invitation permissions
        Permission::firstOrCreate(['name' => 'create invitations']);
        Permission::firstOrCreate(['name' => 'view invitations']);

        // User management permissions
        Permission::firstOrCreate(['name' => 'manage users']);
        Permission::firstOrCreate(['name' => 'view users']);

        // Create roles and assign permissions
        // Admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Resident role
        $residentRole = Role::firstOrCreate(['name' => 'resident']);
        $residentRole->givePermissionTo([
            'view apartments',
            'view water meters',
            'view readings',
            'submit readings',
        ]);

        // Create a default admin user
        $admin = User::where('email', 'admin@example.com')->first();

        if (! $admin) {
            $admin = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        $admin->assignRole($adminRole);
    }
}
