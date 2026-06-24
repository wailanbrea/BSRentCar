<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryZoneResource;
use App\Http\Resources\DeliveryPickupPointResource;
use App\Http\Resources\DeliveryTimeWindowResource;
use App\Http\Resources\DeliveryRequestResource;
use App\Models\DeliveryPickupPoint;
use App\Models\DeliveryTimeWindow;
use App\Models\DeliveryZone;
use App\Models\DeliveryRequest;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminDeliveryController extends Controller
{
    public function __construct(protected readonly DeliveryService $deliveryService)
    {
    }

    // CRUD Zonas de Entrega
    public function indexZones(): AnonymousResourceCollection
    {
        return DeliveryZoneResource::collection(DeliveryZone::orderBy('sort_order')->get());
    }

    public function storeZone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'polygon' => 'required|array',
            'color' => 'nullable|string|max:7',
            'origin_latitude' => 'required|numeric|between:-90,90',
            'origin_longitude' => 'required|numeric|between:-180,180',
            'allows_home_delivery' => 'boolean',
            'base_fee' => 'numeric|min:0',
            'free_radius_km' => 'numeric|min:0',
            'price_per_km' => 'numeric|min:0',
            'max_distance_km' => 'numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $zone = DeliveryZone::create($data);
        return (new DeliveryZoneResource($zone))->response()->setStatusCode(201);
    }

    public function updateZone(Request $request, DeliveryZone $zone): DeliveryZoneResource
    {
        $data = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'polygon' => 'array',
            'color' => 'nullable|string|max:7',
            'origin_latitude' => 'numeric|between:-90,90',
            'origin_longitude' => 'numeric|between:-180,180',
            'allows_home_delivery' => 'boolean',
            'base_fee' => 'numeric|min:0',
            'free_radius_km' => 'numeric|min:0',
            'price_per_km' => 'numeric|min:0',
            'max_distance_km' => 'numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $zone->update($data);
        return new DeliveryZoneResource($zone);
    }

    public function destroyZone(DeliveryZone $zone): JsonResponse
    {
        $zone->delete();
        return response()->json(null, 244)->setStatusCode(204);
    }

    // CRUD Puntos Comerciales
    public function indexPickupPoints(): AnonymousResourceCollection
    {
        return DeliveryPickupPointResource::collection(DeliveryPickupPoint::orderBy('sort_order')->get());
    }

    public function storePickupPoint(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'fee' => 'numeric|min:0',
            'is_active' => 'boolean',
            'opening_hours' => 'nullable|array',
            'notes' => 'nullable|string',
            'sort_order' => 'integer',
        ]);

        $point = DeliveryPickupPoint::create($data);
        return (new DeliveryPickupPointResource($point))->response()->setStatusCode(201);
    }

    public function updatePickupPoint(Request $request, DeliveryPickupPoint $point): DeliveryPickupPointResource
    {
        $data = $request->validate([
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'name' => 'string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'numeric|between:-90,90',
            'longitude' => 'numeric|between:-180,180',
            'fee' => 'numeric|min:0',
            'is_active' => 'boolean',
            'opening_hours' => 'nullable|array',
            'notes' => 'nullable|string',
            'sort_order' => 'integer',
        ]);

        $point->update($data);
        return new DeliveryPickupPointResource($point);
    }

    public function destroyPickupPoint(DeliveryPickupPoint $point): JsonResponse
    {
        $point->delete();
        return response()->json(null, 204);
    }

    // CRUD Ventanas Horarias
    public function indexTimeWindows(): AnonymousResourceCollection
    {
        return DeliveryTimeWindowResource::collection(DeliveryTimeWindow::orderBy('sort_order')->get());
    }

    public function storeTimeWindow(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'label' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'days_of_week' => 'nullable|array',
            'capacity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $window = DeliveryTimeWindow::create($data);
        return (new DeliveryTimeWindowResource($window))->response()->setStatusCode(201);
    }

    public function updateTimeWindow(Request $request, DeliveryTimeWindow $window): DeliveryTimeWindowResource
    {
        $data = $request->validate([
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'label' => 'string|max:255',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i',
            'days_of_week' => 'nullable|array',
            'capacity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $window->update($data);
        return new DeliveryTimeWindowResource($window);
    }

    public function destroyTimeWindow(DeliveryTimeWindow $window): JsonResponse
    {
        $window->delete();
        return response()->json(null, 204);
    }

    // Acciones de Gestión de Solicitudes Logísticas
    public function assignDriver(Request $request, DeliveryRequest $deliveryRequest): DeliveryRequestResource
    {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $driver = User::findOrFail($request->input('driver_id'));

        try {
            $updatedRequest = $this->deliveryService->assignDriver($deliveryRequest, $driver);
            return new DeliveryRequestResource($updatedRequest);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function updateStatus(Request $request, DeliveryRequest $deliveryRequest): DeliveryRequestResource
    {
        $request->validate([
            'status' => 'required|string|in:requested,assigned,in_transit,delivered,returned,cancelled',
        ]);

        $updatedRequest = $this->deliveryService->updateStatus($deliveryRequest, $request->input('status'));
        return new DeliveryRequestResource($updatedRequest);
    }
}
