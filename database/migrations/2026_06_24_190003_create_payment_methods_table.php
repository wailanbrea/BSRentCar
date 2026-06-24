<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla payment_methods — métodos de pago guardados del cliente.
 * Ver docs/09_PAYMENTS_WALLET.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_customer_id')->nullable();
            $table->string('provider_payment_method_id')->nullable();
            $table->string('brand')->nullable();
            $table->char('last_four', 4)->nullable();
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('customer_id');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
