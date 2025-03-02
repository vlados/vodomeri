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
        Schema::table('water_meters', function (Blueprint $table) {
            // Make apartment_id nullable for central meters
            $table->foreignId('apartment_id')->nullable()->change();
        });
        
        // PostgreSQL syntax for altering enum type
        DB::statement("ALTER TABLE water_meters DROP CONSTRAINT IF EXISTS water_meters_type_check");
        DB::statement("ALTER TABLE water_meters ADD CONSTRAINT water_meters_type_check CHECK (type IN ('hot', 'cold', 'central'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert PostgreSQL enum constraints
        DB::statement("ALTER TABLE water_meters DROP CONSTRAINT IF EXISTS water_meters_type_check");
        DB::statement("ALTER TABLE water_meters ADD CONSTRAINT water_meters_type_check CHECK (type IN ('hot', 'cold'))");
        
        Schema::table('water_meters', function (Blueprint $table) {
            // Make apartment_id required again
            $table->foreignId('apartment_id')->nullable(false)->change();
        });
    }
};
