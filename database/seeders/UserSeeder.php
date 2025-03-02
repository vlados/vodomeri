<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update admin user
        $admin = $this->createOrUpdateUser([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ], 'admin');
        
        // Create or update test resident user
        $testUser = $this->createOrUpdateUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ], 'resident');
        
        // Assign some apartments to the test user if they don't already have any
        if ($testUser->apartments()->count() === 0) {
            $apartments = Apartment::inRandomOrder()->take(2)->get();
            foreach ($apartments as $apartment) {
                $testUser->apartments()->attach($apartment->id, [
                    'is_primary' => $apartment->id === $apartments->first()->id
                ]);
            }
        }
        
        // Create a few more residents with different properties
        $resident1 = $this->createOrUpdateUser([
            'name' => 'Maria Petrova',
            'email' => 'maria@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ], 'resident');
        
        if ($resident1->apartments()->count() === 0) {
            // Assign a single apartment
            $apartment = Apartment::where('floor', 2)->inRandomOrder()->first() 
                ?? Apartment::inRandomOrder()->first();
                
            if ($apartment) {
                $resident1->apartments()->attach($apartment->id, ['is_primary' => true]);
            }
        }
        
        $resident2 = $this->createOrUpdateUser([
            'name' => 'Ivan Ivanov',
            'email' => 'ivan@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ], 'resident');
        
        if ($resident2->apartments()->count() === 0) {
            // Assign multiple apartments
            $apartments = Apartment::where('floor', 3)->inRandomOrder()->take(2)->get();
            
            if ($apartments->count() < 2) {
                $apartments = Apartment::inRandomOrder()->take(2)->get();
            }
            
            foreach ($apartments as $apartment) {
                $resident2->apartments()->attach($apartment->id, [
                    'is_primary' => $apartment->id === $apartments->first()->id
                ]);
            }
        }
        
        // Create a user without verification
        $unverified = $this->createOrUpdateUser([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => 'password',
            'email_verified_at' => null,
        ], 'resident');
        
        if ($unverified->apartments()->count() === 0) {
            $apartment = Apartment::inRandomOrder()->first();
            
            if ($apartment) {
                $unverified->apartments()->attach($apartment->id, ['is_primary' => true]);
            }
        }
    }
    
    /**
     * Create a user if they don't exist, or update if they do
     */
    private function createOrUpdateUser(array $attributes, string $role): User
    {
        $user = User::where('email', $attributes['email'])->first();
        
        if (!$user) {
            // Create a new user
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => Hash::make($attributes['password']),
                'email_verified_at' => $attributes['email_verified_at'],
            ]);
            
            $this->command->info("Created user: {$attributes['name']} ({$attributes['email']})");
        } else {
            // Update existing user
            $user->update([
                'name' => $attributes['name'],
                'email_verified_at' => $attributes['email_verified_at'],
            ]);
            
            $this->command->info("Updated existing user: {$attributes['name']} ({$attributes['email']})");
        }
        
        // Assign role if not already assigned
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
            $this->command->info("Assigned role '{$role}' to {$attributes['email']}");
        }
        
        return $user;
    }
}
