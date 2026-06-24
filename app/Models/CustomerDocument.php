<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento de cliente almacenado en disco privado. Ver docs/11_SECURITY.md (§3).
 */
class CustomerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'file_path',
        'original_name',
        'mime',
        'size',
        'status',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'reviewed_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
