<?php

namespace App\Http\Requests\Reservation;

use App\Enums\PickupType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crear reserva. Ver docs/06_API_CONTRACTS.md (POST /reservations).
 */
class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'start_datetime' => ['required', 'date', 'after:now'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'pickup_type' => ['nullable', Rule::enum(PickupType::class)],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'pickup_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'return_type' => ['nullable', Rule::enum(PickupType::class)],
            'return_address' => ['nullable', 'string', 'max:255'],
            'return_location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ];
    }
}
