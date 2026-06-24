<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('file_path'); // Ruta en el disco privado
            $table->string('status'); // draft, pending, signed, void
            $table->timestamp('signed_by_customer_at')->nullable();
            $table->json('signature_meta')->nullable(); // ip, ua, hash, printed_name
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
