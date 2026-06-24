<?php

namespace App\Models;

use App\Enums\VehicleInspectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'vehicle_id',
        'type',
        'fuel_level',
        'mileage',
        'damages',
        'notes',
        'signature_path',
        'accepted_by_customer',
        'inspector_id',
        'inspected_at',
    ];

    protected $casts = [
        'type' => VehicleInspectionType::class,
        'accepted_by_customer' => 'boolean',
        'damages' => 'array',
        'inspected_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InspectionPhoto::class);
    }
}
