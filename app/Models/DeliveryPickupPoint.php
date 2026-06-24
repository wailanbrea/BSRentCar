<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPickupPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_zone_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'fee',
        'is_active',
        'opening_hours',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}
