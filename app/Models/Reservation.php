<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reserva de un vehículo por un rango. Ver docs/10_RESERVATIONS_FLOW.md.
 */
class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_number', 'customer_id', 'vehicle_id',
        'pickup_location_id', 'return_location_id', 'insurance_plan_id',
        'start_datetime', 'end_datetime',
        'pickup_type', 'pickup_address', 'pickup_latitude', 'pickup_longitude',
        'return_type', 'return_address', 'return_latitude', 'return_longitude',
        'base_price', 'delivery_fee', 'insurance_fee', 'deposit_amount',
        'discount_amount', 'tax_amount', 'total_amount', 'currency',
        'payment_status', 'reservation_status', 'contract_status',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'base_price' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'insurance_fee' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'payment_status' => PaymentStatus::class,
            'reservation_status' => ReservationStatus::class,
            'contract_status' => ContractStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'pickup_location_id');
    }

    public function returnLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'return_location_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReservationStatusLog::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function depositTransactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class);
    }

    public function contract(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Contract::class);
    }

    public function deliveryRequests(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class);
    }
}
