<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\CatalogFilterRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catálogo público de vehículos. Contratos: docs/06_API_CONTRACTS.md (Vehicles/Catalog).
 */
class VehicleController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {
    }

    public function index(CatalogFilterRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $query = Vehicle::query()
            ->rentable()
            ->filter($filters)
            ->with('primaryImage');

        // Si hay rango de fechas, excluir los no disponibles (estado + bloqueos).
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $start = CarbonImmutable::parse($filters['start_date']);
            $end = CarbonImmutable::parse($filters['end_date']);
            $this->availability->filterAvailable($query, $start, $end);
        }

        $query = match ($filters['sort'] ?? null) {
            'price_asc' => $query->orderBy('daily_price'),
            'price_desc' => $query->orderByDesc('daily_price'),
            'rating' => $query->orderByDesc('rating_avg'),
            default => $query->latest(),
        };

        return VehicleResource::collection(
            $query->paginate($filters['per_page'] ?? 12)->withQueryString()
        );
    }

    public function show(Vehicle $vehicle): VehicleResource
    {
        return new VehicleResource(
            $vehicle->load(['images', 'features', 'priceRules', 'location'])
        );
    }

    public function availability(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $start = CarbonImmutable::parse($validated['start_date']);
        $end = CarbonImmutable::parse($validated['end_date']);

        $available = $this->availability->isAvailable($vehicle, $start, $end);

        return response()->json([
            'available' => $available,
            'quote' => $available ? $this->pricing->quote($vehicle, $start, $end) : null,
        ]);
    }
}
