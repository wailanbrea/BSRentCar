<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sucursal / ubicación física. Ver docs/02_BUSINESS_RULES.md (BR-L01..L03).
 */
class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'address', 'city', 'latitude', 'longitude', 'phone', 'opening_hours', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
