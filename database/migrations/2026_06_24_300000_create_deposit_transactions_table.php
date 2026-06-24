<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // stripe, paypal
            $table->string('provider_reference'); // authorization ID or charge/intent ID
            $table->string('type'); // hold, capture, partial_capture, release, charge
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status'); // authorized, captured, released, failed
            $table->string('reason')->nullable();
            $table->decimal('captured_amount', 12, 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_transactions');
    }
};
