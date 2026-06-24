<?php

namespace App\Models;

use App\Enums\PaymentAttemptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de cada intento de pago contra el proveedor. Ver docs/09_PAYMENTS_WALLET.md.
 */
class PaymentAttempt extends Model
{
    use HasFactory;

    /** Solo se usa created_at, no updated_at. */
    const UPDATED_AT = null;

    protected $fillable = [
        'payment_id', 'reservation_id', 'customer_id',
        'provider', 'provider_reference',
        'amount', 'currency', 'status',
        'error_code', 'error_message',
        'request_payload', 'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentAttemptStatus::class,
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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
