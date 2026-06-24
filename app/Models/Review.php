<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'customer_id',
        'vehicle_id',
        'rating_vehicle',
        'rating_cleanliness',
        'rating_service',
        'rating_delivery',
        'rating_overall',
        'comment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating_vehicle' => 'integer',
            'rating_cleanliness' => 'integer',
            'rating_service' => 'integer',
            'rating_delivery' => 'integer',
            'rating_overall' => 'integer',
            'status' => ReviewStatus::class,
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
