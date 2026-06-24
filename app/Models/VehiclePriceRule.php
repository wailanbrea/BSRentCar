<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Regla de precio por temporada/duración. Ver docs/04_DATABASE_SCHEMA.md (#9).
 */
class VehiclePriceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'type', 'start_date', 'end_date', 'min_days',
        'price_modifier_type', 'price_modifier_value', 'priority',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'min_days' => 'integer',
            'price_modifier_value' => 'decimal:2',
            'priority' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
