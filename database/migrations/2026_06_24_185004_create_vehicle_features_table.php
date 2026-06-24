<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla vehicle_features. Ver docs/04_DATABASE_SCHEMA.md (#8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->timestamps();

            $table->index('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_features');
    }
};
