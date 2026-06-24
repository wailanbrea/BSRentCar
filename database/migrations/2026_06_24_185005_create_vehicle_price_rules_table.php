<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla vehicle_price_rules. Ver docs/04_DATABASE_SCHEMA.md (#9), BR-V03.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // seasonal|weekend|min_days|promo
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('min_days')->nullable();
            $table->string('price_modifier_type')->default('percent'); // fixed|percent
            $table->decimal('price_modifier_value', 12, 2)->default(0);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_price_rules');
    }
};
