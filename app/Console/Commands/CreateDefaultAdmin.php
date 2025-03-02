<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
            (new RolesAndPermissionsSeeder())->run();
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