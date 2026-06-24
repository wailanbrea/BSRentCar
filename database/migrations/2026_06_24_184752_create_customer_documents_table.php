<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla customer_documents (storage privado). Ver docs/04_DATABASE_SCHEMA.md (#5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
