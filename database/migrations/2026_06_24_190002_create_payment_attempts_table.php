<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla payment_attempts — registro de cada intento contra el proveedor.
 * Solo created_at (no se actualiza). Ver docs/09_PAYMENTS_WALLET.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('DOP');
            $table->string('status')->default('initiated');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
