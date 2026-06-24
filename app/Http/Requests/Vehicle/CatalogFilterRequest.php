<?php

namespace App\Http\Requests\Vehicle;

use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del catálogo público. Ver docs/06_API_CONTRACTS.md (GET /vehicles).
 */
class CatalogFilterRequest extends FormRequest
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
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'after:start_date', 'required_with:start_date'],
            'category' => ['nullable', Rule::enum(VehicleCategory::class)],
            'transmission' => ['nullable', Rule::enum(Transmission::class)],
            'seats_min' => ['nullable', 'integer', 'min:1', 'max:20'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'sort' => ['nullable', Rule::in(['price_asc', 'price_desc', 'rating'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
