<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla customers. Ver docs/04_DATABASE_SCHEMA.md (#4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('license_number')->nullable();
            $table->string('verification_status')->default('unverified');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
