<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla vehicle_images. Ver docs/04_DATABASE_SCHEMA.md (#7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_images');
    }
};
