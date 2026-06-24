<?php

namespace App\Http\Requests\Admin;

use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización vía middleware permission:vehicles.update
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'category' => ['sometimes', 'required', Rule::enum(VehicleCategory::class)],
            'transmission' => ['sometimes', 'required', Rule::enum(Transmission::class)],
            'seats' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'doors' => ['nullable', 'integer', 'min:1', 'max:8'],
            'fuel_type' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:40'],
            'plate' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('vehicles', 'plate')->ignore($vehicleId)],
            'vin' => ['nullable', 'string', 'max:40'],
            'daily_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'status' => ['nullable', Rule::enum(VehicleStatus::class)],
            'description' => ['nullable', 'string'],
            'rules' => ['nullable', 'array'],
        ];
    }
}
