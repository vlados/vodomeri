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
        Schema::create('water_meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number');
            $table->enum('type', ['hot', 'cold']);
            $table->string('location')->nullable();
            $table->date('installation_date')->nullable();
            $table->decimal('initial_reading', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_meters');
    }
};
