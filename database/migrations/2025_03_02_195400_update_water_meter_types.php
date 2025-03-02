<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update any existing 'central' meters to 'central-cold'
        DB::table('water_meters')
            ->where('type', 'central')
            ->update(['type' => 'central-cold']);
            
        // PostgreSQL syntax for altering enum type
        DB::statement("ALTER TABLE water_meters DROP CONSTRAINT IF EXISTS water_meters_type_check");
        DB::statement("ALTER TABLE water_meters ADD CONSTRAINT water_meters_type_check CHECK (type IN ('hot', 'cold', 'central-hot', 'central-cold'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update 'central-hot' and 'central-cold' back to 'central'
        DB::table('water_meters')
            ->whereIn('type', ['central-hot', 'central-cold'])
            ->update(['type' => 'central']);
            
        // Revert PostgreSQL enum constraints
        DB::statement("ALTER TABLE water_meters DROP CONSTRAINT IF EXISTS water_meters_type_check");
        DB::statement("ALTER TABLE water_meters ADD CONSTRAINT water_meters_type_check CHECK (type IN ('hot', 'cold', 'central'))");
    }
};
