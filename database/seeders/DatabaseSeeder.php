<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
//            ApartmentSeeder::class,
//            UserSeeder::class,         // Now calling our new UserSeeder
//            WaterMeterAndReadingSeeder::class,
        ]);
    }
}
