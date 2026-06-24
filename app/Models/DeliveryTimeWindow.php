<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTimeWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_zone_id',
        'label',
        'start_time',
        'end_time',
        'days_of_week',
        'capacity',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}
