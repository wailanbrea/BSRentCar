<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Perfil de cliente. Ver docs/02_BUSINESS_RULES.md (Clientes).
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'birthdate',
        'address',
        'city',
        'country',
        'license_number',
        'verification_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Edad cumplida a una fecha dada (por defecto hoy). Ver BR-C08.
     */
    public function ageAt(?\DateTimeInterface $date = null): ?int
    {
        if (! $this->birthdate) {
            return null;
        }

        return (int) $this->birthdate->diffInYears($date ?? now());
    }

    /**
     * ¿Tiene una licencia de conducir aprobada? Ver BR-C09.
     */
    public function hasApprovedLicense(): bool
    {
        return $this->documents()
            ->where('type', DocumentType::License->value)
            ->where('status', DocumentStatus::Approved->value)
            ->exists();
    }

    /**
     * Elegibilidad para rentar (edad >= 18 a la fecha de inicio + licencia aprobada).
     * Devuelve [] si es elegible, o un array de razones si no.
     * Ver docs/10_RESERVATIONS_FLOW.md (gate 4.0).
     */
    public function rentalEligibilityErrors(?\DateTimeInterface $startDate = null): array
    {
        $errors = [];

        $age = $this->ageAt($startDate);
        if ($age === null || $age < 18) {
            $errors[] = 'min_age_18';
        }

        if (! $this->hasApprovedLicense()) {
            $errors[] = 'license_not_approved';
        }

        return $errors;
    }
}
