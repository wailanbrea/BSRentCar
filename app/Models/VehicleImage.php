<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleImage extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_id', 'path', 'is_primary', 'sort_order', 'alt'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
