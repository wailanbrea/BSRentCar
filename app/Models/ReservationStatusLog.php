<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationStatusLog extends Model
{
    protected $fillable = [
        'reservation_id', 'from_status', 'to_status', 'changed_by', 'reason',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
