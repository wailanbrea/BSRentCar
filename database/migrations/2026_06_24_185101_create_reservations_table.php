<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla reservations. Ver docs/04_DATABASE_SCHEMA.md (#11) y docs/10_RESERVATIONS_FLOW.md.
 * Dinero en decimal(12,2). Índice de solape (vehicle_id, start, end).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('return_location_id')->nullable()->constrained('locations')->nullOnDelete();
            // FK a insurance_plans se añadirá cuando exista esa tabla (Fase seguro).
            $table->unsignedBigInteger('insurance_plan_id')->nullable();

            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->string('pickup_type')->default('office');
            $table->string('pickup_address')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->string('return_type')->nullable();
            $table->string('return_address')->nullable();
            $table->decimal('return_latitude', 10, 7)->nullable();
            $table->decimal('return_longitude', 10, 7)->nullable();

            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('insurance_fee', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('DOP');

            $table->string('payment_status')->default('pending');
            $table->string('reservation_status')->default('pending_payment');
            $table->string('contract_status')->default('none');

            $table->timestamps();

            $table->index(['vehicle_id', 'start_datetime', 'end_datetime'], 'res_vehicle_range_idx');
            $table->index('customer_id');
            $table->index('reservation_status');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
