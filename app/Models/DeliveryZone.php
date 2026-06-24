<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'polygon',
        'color',
        'origin_latitude',
        'origin_longitude',
        'allows_home_delivery',
        'base_fee',
        'free_radius_km',
        'price_per_km',
        'max_distance_km',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'polygon' => 'array',
        'allows_home_delivery' => 'boolean',
        'base_fee' => 'decimal:2',
        'free_radius_km' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'max_distance_km' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function pickupPoints(): HasMany
    {
        return $this->hasMany(DeliveryPickupPoint::class);
    }

    public function timeWindows(): HasMany
    {
        return $this->hasMany(DeliveryTimeWindow::class);
    }

    public function deliveryRequests(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class);
    }
}
