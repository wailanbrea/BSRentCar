<?php

namespace App\Models;

use App\Enums\PaymentMethodStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Método de pago guardado (tarjeta tokenizada). Ver docs/09_PAYMENTS_WALLET.md.
 */
class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'provider',
        'provider_customer_id', 'provider_payment_method_id',
        'brand', 'last_four', 'exp_month', 'exp_year',
        'is_default', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => PaymentMethodStatus::class,
            'exp_month' => 'integer',
            'exp_year' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Solo métodos activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PaymentMethodStatus::Active);
    }

    /**
     * Solo el método marcado como predeterminado.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
