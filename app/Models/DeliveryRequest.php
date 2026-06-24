<?php

namespace App\Models;

use App\Enums\DeliveryRequestStatus;
use App\Enums\DeliveryRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'delivery_zone_id',
        'pickup_point_id',
        'delivery_time_window_id',
        'direction',
        'type',
        'address',
        'latitude',
        'longitude',
        'distance_km',
        'fee',
        'scheduled_date',
        'status',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'type' => DeliveryRequestType::class,
        'status' => DeliveryRequestStatus::class,
        'distance_km' => 'decimal:2',
        'fee' => 'decimal:2',
        'scheduled_date' => 'date',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(DeliveryPickupPoint::class, 'pickup_point_id');
    }

    public function timeWindow(): BelongsTo
    {
        return $this->belongsTo(DeliveryTimeWindow::class, 'delivery_time_window_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
