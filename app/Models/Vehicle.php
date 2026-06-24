<?php

namespace App\Models;

use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Enums\Transmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vehículo. Ver docs/02_BUSINESS_RULES.md (Vehículos) y docs/04_DATABASE_SCHEMA.md (#6).
 */
class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'brand', 'model', 'year', 'category', 'transmission', 'seats', 'doors',
        'fuel_type', 'color', 'plate', 'vin', 'daily_price', 'deposit_amount', 'currency',
        'mileage', 'location_id', 'status', 'description', 'rules',
    ];

    protected function casts(): array
    {
        return [
            'category' => VehicleCategory::class,
            'transmission' => Transmission::class,
            'status' => VehicleStatus::class,
            'daily_price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'rules' => 'array',
            'seats' => 'integer',
            'doors' => 'integer',
            'year' => 'integer',
        ];
    }

    // Relaciones ----------------------------------------------------------

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(VehicleImage::class)->where('is_primary', true);
    }

    public function features(): HasMany
    {
        return $this->hasMany(VehicleFeature::class);
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(VehiclePriceRule::class);
    }

    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(VehicleAvailabilityBlock::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // Scopes de catálogo --------------------------------------------------

    /** Solo vehículos en estado rentable (no mantenimiento/bloqueado/fuera de servicio). */
    public function scopeRentable(Builder $query): Builder
    {
        return $query->whereNotIn('status', array_map(
            fn (VehicleStatus $s) => $s->value,
            VehicleStatus::nonRentable()
        ));
    }

    /**
     * Aplica filtros del catálogo. Ver docs/06_API_CONTRACTS.md (GET /vehicles).
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['category'] ?? null, fn (Builder $q, $v) => $q->where('category', $v))
            ->when($filters['transmission'] ?? null, fn (Builder $q, $v) => $q->where('transmission', $v))
            ->when($filters['seats_min'] ?? null, fn (Builder $q, $v) => $q->where('seats', '>=', (int) $v))
            ->when($filters['price_min'] ?? null, fn (Builder $q, $v) => $q->where('daily_price', '>=', $v))
            ->when($filters['price_max'] ?? null, fn (Builder $q, $v) => $q->where('daily_price', '<=', $v))
            ->when($filters['location_id'] ?? null, fn (Builder $q, $v) => $q->where('location_id', (int) $v));
    }
}
