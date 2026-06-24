<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla vehicle_availability_blocks (bloqueos manuales). Ver docs/04_DATABASE_SCHEMA.md (#10).
 * Participan en el cálculo de solape junto con reservas bloqueantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('reason')->default('blocked'); // maintenance|blocked|internal|other
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'start_datetime', 'end_datetime'], 'vab_vehicle_range_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_availability_blocks');
    }
};
