<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla vehicles. Ver docs/04_DATABASE_SCHEMA.md (#6), BR-V01..V07.
 * Dinero en decimal(12,2). La disponibilidad NO se determina solo por status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('category')->default('economy');
            $table->string('transmission')->default('automatic');
            $table->unsignedTinyInteger('seats')->default(5);
            $table->unsignedTinyInteger('doors')->default(4);
            $table->string('fuel_type')->nullable();
            $table->string('color')->nullable();
            $table->string('plate')->unique();
            $table->string('vin')->nullable();
            $table->decimal('daily_price', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('DOP');
            $table->unsignedBigInteger('mileage')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('status')->default('available');
            $table->text('description')->nullable();
            $table->json('rules')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('category');
            $table->index('transmission');
            $table->index('daily_price');
            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
