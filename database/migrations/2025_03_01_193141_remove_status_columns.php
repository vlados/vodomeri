<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('water_meters', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // For readings, we'll convert the enum to string to avoid issues with removing enums
        Schema::table('readings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('water_meters', function (Blueprint $table) {
            $table->string('status')->nullable()->default(null)->after('initial_reading');
        });

        Schema::table('readings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('photo_path');
        });
    }
};
