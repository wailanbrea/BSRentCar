<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            
            $table->tinyInteger('rating_vehicle');
            $table->tinyInteger('rating_cleanliness');
            $table->tinyInteger('rating_service');
            $table->tinyInteger('rating_delivery');
            $table->tinyInteger('rating_overall');
            
            $table->text('comment')->nullable();
            $table->string('status')->default('visible'); // visible, hidden
            
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
