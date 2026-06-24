<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de cambios de estado de la reserva. Ver docs/04_DATABASE_SCHEMA.md (#12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_status_logs');
    }
};
