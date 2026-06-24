<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloqueo manual de disponibilidad. Ver docs/04_DATABASE_SCHEMA.md (#10).
 */
class VehicleAvailabilityBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'start_datetime', 'end_datetime', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
