<?php

namespace App\Models;

use App\Enums\ContractDocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'number',
        'file_path',
        'status',
        'signed_by_customer_at',
        'signature_meta',
        'generated_by',
    ];

    protected $casts = [
        'status' => ContractDocumentStatus::class,
        'signed_by_customer_at' => 'datetime',
        'signature_meta' => 'array',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
