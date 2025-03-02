<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateDefaultAdmin extends Command
{
    protected $signature = 'app:create-default-admin
                            {--seed-roles : Run the RolesAndPermissionsSeeder}';
    protected $description = 'Create the default admin user';

    public function handle(): int
    {
        // Seed roles and permissions if the option is specified
        if ($this->option('seed-roles')) {
            $this->info('Seeding roles and permissions...');
            
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
            
            $this->info('Roles and permissions seeded successfully.');
        }

        $email = 'dev@vladko.dev';
        $password = 'vodomeri';

        $user = User::firstOrNew(['email' => $email]);
        
        if ($user->exists) {
            $this->info("Admin user with email {$email} already exists.");
            
            if ($this->confirm('Do you want to reset the password?')) {
                $user->password = Hash::make($password);
                $user->save();
                $this->info('Password has been reset.');
            }
        } else {
            $user->name = 'Admin';
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->email_verified_at = now();
            $user->save();
            
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $user->assignRole($adminRole);
            
            $this->info("Admin user created with email: {$email} and password: {$password}");
        }

        return Command::SUCCESS;
    }
}