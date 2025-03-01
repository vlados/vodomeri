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
        Permission::create(['name' => 'view apartments']);
        Permission::create(['name' => 'create apartments']);
        Permission::create(['name' => 'edit apartments']);
        Permission::create(['name' => 'delete apartments']);
        
        // Water meter permissions
        Permission::create(['name' => 'view water meters']);
        Permission::create(['name' => 'create water meters']);
        Permission::create(['name' => 'edit water meters']);
        Permission::create(['name' => 'delete water meters']);
        
        // Reading permissions
        Permission::create(['name' => 'view readings']);
        Permission::create(['name' => 'submit readings']);
        Permission::create(['name' => 'approve readings']);
        Permission::create(['name' => 'reject readings']);
        
        // Invitation permissions
        Permission::create(['name' => 'create invitations']);
        Permission::create(['name' => 'view invitations']);
        
        // User management permissions
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'view users']);

        // Create roles and assign permissions
        // Admin role
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
        
        // Resident role
        $residentRole = Role::create(['name' => 'resident']);
        $residentRole->givePermissionTo([
            'view apartments',
            'view water meters',
            'view readings',
            'submit readings',
        ]);
        
        // Create a default admin user
        $admin = User::where('email', 'admin@example.com')->first();
        
        if (!$admin) {
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
