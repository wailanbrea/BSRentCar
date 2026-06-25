<?php

namespace App\Http\Controllers\Web\Client;

use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'after:start_date', 'required_with:start_date'],
            'category' => ['nullable'],
            'transmission' => ['nullable'],
            'seats_min' => ['nullable', 'integer', 'min:1'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable'],
        ]);

        $query = Vehicle::rentable()->filter($filters)->with('primaryImage');

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $this->availability->filterAvailable(
                $query,
                CarbonImmutable::parse($filters['start_date']),
                CarbonImmutable::parse($filters['end_date']),
            );
        }

        $query = match ($filters['sort'] ?? null) {
            'price_asc' => $query->orderBy('daily_price'),
            'price_desc' => $query->orderByDesc('daily_price'),
            'rating' => $query->orderByDesc('rating_avg'),
            default => $query->latest(),
        };

        $vehicles = $query->paginate(9)->withQueryString();

        return view('client.catalog.index', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'categories' => VehicleCategory::cases(),
            'transmissions' => Transmission::cases(),
        ]);
    }

    public function show(Vehicle $vehicle): View
    {
        abort_if($vehicle->trashed(), 404);

        $vehicle->load(['images', 'features', 'location']);
        $reviews = $vehicle->reviews()
            ->where('status', 'visible')
            ->with('customer')
            ->latest()
            ->limit(6)
            ->get();

        return view('client.catalog.show', compact('vehicle', 'reviews'));
    }
}
