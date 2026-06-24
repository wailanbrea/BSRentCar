<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\QuoteDeliveryRequest;
use App\Http\Resources\DeliveryZoneResource;
use App\Http\Resources\DeliveryPickupPointResource;
use App\Http\Resources\DeliveryTimeWindowResource;
use App\Models\DeliveryPickupPoint;
use App\Models\DeliveryTimeWindow;
use App\Models\DeliveryZone;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryController extends Controller
{
    public function __construct(protected readonly DeliveryService $deliveryService)
    {
    }

    /**
     * Lista las zonas de entrega activas.
     */
    public function indexZones(Request $request): AnonymousResourceCollection
    {
        $zones = DeliveryZone::where('is_active', true)->orderBy('sort_order')->get();
        return DeliveryZoneResource::collection($zones);
    }

    /**
     * Lista los puntos comerciales de entrega (sucursales).
     */
    public function indexPickupPoints(Request $request): AnonymousResourceCollection
    {
        $pointsQuery = DeliveryPickupPoint::where('is_active', true);

        if ($request->filled(['lat', 'lng'])) {
            $lat = (float) $request->input('lat');
            $lng = (float) $request->input('lng');

            $points = $pointsQuery->get()->map(function ($point) use ($lat, $lng) {
                $point->distance_km = $this->deliveryService->calculateDistance($point->latitude, $point->longitude, $lat, $lng);
                return $point;
            })->sortBy('distance_km')->values();

            return DeliveryPickupPointResource::collection($points);
        }

        $points = $pointsQuery->orderBy('sort_order')->get();
        return DeliveryPickupPointResource::collection($points);
    }

    /**
     * Lista las ventanas horarias aplicables.
     */
    public function indexTimeWindows(Request $request): AnonymousResourceCollection
    {
        $query = DeliveryTimeWindow::where('is_active', true);

        if ($request->filled('delivery_zone_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('delivery_zone_id', $request->input('delivery_zone_id'))
                  ->orWhereNull('delivery_zone_id');
            });
        }

        $windows = $query->orderBy('sort_order')->get();
        return DeliveryTimeWindowResource::collection($windows);
    }

    /**
     * Cotiza cobertura y costos para una entrega a domicilio.
     */
    public function quote(QuoteDeliveryRequest $request): JsonResponse
    {
        $quote = $this->deliveryService->quoteDelivery(
            $request->input('type'),
            (float) $request->input('latitude'),
            (float) $request->input('longitude')
        );

        return response()->json($quote);
    }
}
