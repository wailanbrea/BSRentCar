<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla payments. Ver docs/09_PAYMENTS_WALLET.md.
 * Dinero en decimal(12,2). Moneda por defecto DOP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_subtype')->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->string('provider_order_id')->nullable();
            $table->string('provider_capture_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('DOP');
            $table->string('status')->default('pending');
            $table->string('payment_type');
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('reservation_id');
            $table->index('customer_id');
            $table->index('provider');
            $table->index('status');
            $table->index('provider_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
