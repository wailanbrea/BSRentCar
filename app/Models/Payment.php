<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pago registrado contra un proveedor. Ver docs/09_PAYMENTS_WALLET.md.
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'customer_id', 'provider', 'provider_subtype',
        'provider_payment_id', 'provider_order_id', 'provider_capture_id',
        'amount', 'currency', 'status', 'payment_type',
        'idempotency_key', 'metadata', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'payment_type' => PaymentType::class,
            'metadata' => 'array',
            'paid_at' => 'datetime',
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

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    /**
     * Filtrar por proveedor (stripe, paypal, wallet, manual).
     */
    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Solo pagos completados (status = paid).
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Paid);
    }
}
