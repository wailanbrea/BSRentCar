<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
use App\Http\Requests\Admin\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Gestión administrativa de vehículos. Ver docs/08_ADMIN_PANEL.md (Vehículos).
 * Autorización por permisos Spatie (registrada en routes/api.php).
 */
class VehicleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return VehicleResource::collection(
            Vehicle::with('primaryImage')->latest()->paginate(20)
        );
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return (new VehicleResource($vehicle))->response()->setStatusCode(201);
    }

    public function show(Vehicle $vehicle): VehicleResource
    {
        return new VehicleResource($vehicle->load(['images', 'features', 'priceRules', 'location']));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): VehicleResource
    {
        $vehicle->update($request->validated());

        return new VehicleResource($vehicle->refresh());
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json([], 204);
    }
}
