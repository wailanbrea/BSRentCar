<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla locations (sucursales). Ver docs/04_DATABASE_SCHEMA.md (#10b), BR-L01..L03.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('branch'); // branch|airport|other
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->json('opening_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
