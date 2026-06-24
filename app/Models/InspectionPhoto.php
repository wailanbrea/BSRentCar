<?php

namespace App\Models;

use App\Enums\InspectionPhotoPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_inspection_id',
        'path',
        'position',
        'note',
    ];

    protected $casts = [
        'position' => InspectionPhotoPosition::class,
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(VehicleInspection::class, 'vehicle_inspection_id');
    }
}
