<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('polygon'); // Coordenadas del polígono GeoJSON
            $table->string('color')->nullable();
            $table->decimal('origin_latitude', 10, 7);
            $table->decimal('origin_longitude', 10, 7);
            $table->boolean('allows_home_delivery')->default(true);
            $table->decimal('base_fee', 12, 2)->default(0);
            $table->decimal('free_radius_km', 6, 2)->default(0);
            $table->decimal('price_per_km', 12, 2)->default(0);
            $table->decimal('max_distance_km', 6, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('name');
        });

        Schema::create('delivery_pickup_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('fee', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('opening_hours')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('delivery_zone_id');
            $table->index('is_active');
        });

        Schema::create('delivery_time_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->string('label');
            $table->time('start_time');
            $table->time('end_time');
            $table->json('days_of_week')->nullable();
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('delivery_zone_id');
            $table->index('is_active');
        });

        Schema::create('delivery_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->foreignId('pickup_point_id')->nullable()->constrained('delivery_pickup_points')->nullOnDelete();
            $table->foreignId('delivery_time_window_id')->nullable()->constrained('delivery_time_windows')->nullOnDelete();
            $table->string('direction'); // pickup, return
            $table->string('type'); // pickup_point, home, office, airport, hotel, custom
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('fee', 12, 2)->default(0);
            $table->date('scheduled_date');
            $table->string('status'); // requested, assigned, in_transit, delivered, returned, cancelled
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('reservation_id');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('delivery_zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_requests');
        Schema::dropIfExists('delivery_time_windows');
        Schema::dropIfExists('delivery_pickup_points');
        Schema::dropIfExists('delivery_zones');
    }
};
