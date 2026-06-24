<?php

namespace App\Models;

use App\Enums\DepositTransactionStatus;
use App\Enums\DepositTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa una transacción del ciclo de vida del depósito de seguridad.
 */
class DepositTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'customer_id',
        'provider',
        'provider_reference',
        'type',
        'amount',
        'currency',
        'status',
        'reason',
        'captured_amount',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'captured_amount' => 'decimal:2',
            'type'            => DepositTransactionType::class,
            'status'          => DepositTransactionStatus::class,
            'expires_at'      => 'datetime',
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
}
