<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // initial, final
            $table->string('fuel_level'); // Nivel de combustible (ej. 8/8, 100%, 3/4)
            $table->integer('mileage'); // Kilometraje del vehículo
            $table->json('damages')->nullable(); // Registro de daños detectados
            $table->text('notes')->nullable();
            $table->string('signature_path')->nullable(); // Ruta de firma del cliente
            $table->boolean('accepted_by_customer')->default(false);
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at');
            $table->timestamps();

            $table->index('reservation_id');
            $table->index('vehicle_id');
            $table->index('type');
        });

        Schema::create('inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_inspection_id')->constrained('vehicle_inspections')->cascadeOnDelete();
            $table->string('path'); // Ruta en storage privado
            $table->string('position'); // front, back, left, right, interior, damage, other
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('vehicle_inspection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_photos');
        Schema::dropIfExists('vehicle_inspections');
    }
};
