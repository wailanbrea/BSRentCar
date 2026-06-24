<?php

namespace App\Services;

use App\Enums\DeliveryRequestStatus;
use App\Enums\DeliveryRequestType;
use App\Enums\ReservationStatus;
use App\Models\DeliveryPickupPoint;
use App\Models\DeliveryRequest;
use App\Models\DeliveryZone;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    /**
     * Algoritmo de Ray-Casting para geofencing.
     * Verifica si una coordenada lat/lng está dentro del polígono GeoJSON de una zona.
     */
    public function isPointInPolygon(float $latitude, float $longitude, array $polygon): bool
    {
        $vertices = [];
        if (isset($polygon['coordinates'][0])) {
            $vertices = $polygon['coordinates'][0];
        } elseif (isset($polygon[0])) {
            $vertices = $polygon;
        }

        $numVertices = count($vertices);
        if ($numVertices < 3) {
            return false;
        }

        $inside = false;
        $j = $numVertices - 1;

        for ($i = 0; $i < $numVertices; $i++) {
            $pI = $vertices[$i];
            $pJ = $vertices[$j];

            // Convención estándar GeoJSON: [longitude, latitude]
            $iLng = $pI[0] ?? $pI['lng'] ?? $pI['longitude'];
            $iLat = $pI[1] ?? $pI['lat'] ?? $pI['latitude'];
            $jLng = $pJ[0] ?? $pJ['lng'] ?? $pJ['longitude'];
            $jLat = $pJ[1] ?? $pJ['lat'] ?? $pJ['latitude'];

            $intersect = (($iLat > $latitude) != ($jLat > $latitude))
                && ($longitude < ($jLng - $iLng) * ($latitude - $iLat) / max(0.00001, $jLat - $iLat) + $iLng);

            if ($intersect) {
                $inside = !$inside;
            }
            $j = $i;
        }

        return $inside;
    }

    /**
     * Calcula la distancia entre dos coordenadas geográficas utilizando la Fórmula de Haversine.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Cotiza el costo de entrega a domicilio para una coordenada dada.
     */
    public function quoteDelivery(string $type, float $lat, float $lng): array
    {
        $activeZones = DeliveryZone::where('is_active', true)->orderBy('sort_order')->get();
        
        foreach ($activeZones as $zone) {
            if ($zone->allows_home_delivery && $this->isPointInPolygon($lat, $lng, $zone->polygon)) {
                $distance = $this->calculateDistance($zone->origin_latitude, $zone->origin_longitude, $lat, $lng);
                
                if ($distance > $zone->max_distance_km) {
                    continue; // Excede distancia máxima permitida de esta zona
                }

                $excessDistance = max(0.0, $distance - $zone->free_radius_km);
                $fee = $zone->base_fee + ($excessDistance * $zone->price_per_km);

                return [
                    'eligible' => true,
                    'zone_id' => $zone->id,
                    'distance_km' => round($distance, 2),
                    'fee' => number_format($fee, 2, '.', ''),
                    'currency' => $zone->currency,
                ];
            }
        }

        // Si no es elegible, sugerir los 3 puntos comerciales (pickup points) activos más cercanos
        $points = DeliveryPickupPoint::where('is_active', true)->get();
        $suggestions = [];
        foreach ($points as $p) {
            $dist = $this->calculateDistance($p->latitude, $p->longitude, $lat, $lng);
            $suggestions[] = [
                'id' => $p->id,
                'name' => $p->name,
                'address' => $p->address,
                'latitude' => $p->latitude,
                'longitude' => $p->longitude,
                'fee' => number_format($p->fee, 2, '.', ''),
                'distance_km' => round($dist, 2),
            ];
        }

        usort($suggestions, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
        $suggestions = array_slice($suggestions, 0, 3);

        return [
            'eligible' => false,
            'reason' => 'out_of_coverage',
            'suggested_pickup_points' => $suggestions,
        ];
    }

    /**
     * Registra una nueva petición de entrega o devolución.
     */
    public function createRequest(Reservation $reservation, array $data): DeliveryRequest
    {
        return DB::transaction(function () use ($reservation, $data) {
            $type = $data['type'];
            $fee = 0.00;
            $zoneId = null;
            $pointId = null;
            $distance = null;

            if ($type === 'home' || $type === 'custom') {
                $quote = $this->quoteDelivery($type, $data['latitude'], $data['longitude']);
                if (!$quote['eligible']) {
                    throw new \DomainException("La ubicación seleccionada está fuera de cobertura.");
                }
                $zoneId = $quote['zone_id'];
                $distance = $quote['distance_km'];
                $fee = $quote['fee'];
            } elseif ($type === 'pickup_point') {
                $point = DeliveryPickupPoint::findOrFail($data['pickup_point_id']);
                $pointId = $point->id;
                $zoneId = $point->delivery_zone_id;
                $fee = $point->fee;
            }

            return DeliveryRequest::create([
                'reservation_id' => $reservation->id,
                'delivery_zone_id' => $zoneId,
                'pickup_point_id' => $pointId,
                'delivery_time_window_id' => $data['delivery_time_window_id'] ?? null,
                'direction' => $data['direction'],
                'type' => $type,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'distance_km' => $distance,
                'fee' => $fee,
                'scheduled_date' => $data['scheduled_date'],
                'status' => DeliveryRequestStatus::Requested->value,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Asigna un conductor a la entrega.
     */
    public function assignDriver(DeliveryRequest $request, User $driver): DeliveryRequest
    {
        if (!$driver->hasRole('driver')) {
            throw new \InvalidArgumentException("El usuario asignado debe tener el rol de conductor (driver).");
        }

        $request->update([
            'assigned_to' => $driver->id,
            'status' => DeliveryRequestStatus::Assigned->value,
        ]);

        return $request->refresh();
    }

    /**
     * Actualiza el estado de la entrega y sincroniza la reservación de ser necesario.
     */
    public function updateStatus(DeliveryRequest $request, string $status): DeliveryRequest
    {
        $oldStatus = $request->status;

        $request->update(['status' => $status]);
        $request = $request->refresh();

        // Sincronización automática de estado de la reservación
        $reservation = $request->reservation;
        $stateMachine = app(ReservationStateMachine::class);

        if ($status === DeliveryRequestStatus::Delivered->value && $request->direction === 'pickup') {
            if ($reservation->reservation_status === ReservationStatus::DeliveryAssigned) {
                // Transicionar la reservación usando el conductor asignado
                $stateMachine->transition($reservation, ReservationStatus::Delivered, $request->driver, "Entrega a domicilio confirmada.");
            }
        } elseif ($status === DeliveryRequestStatus::Returned->value && $request->direction === 'return') {
            if ($reservation->reservation_status === ReservationStatus::ReturnPending) {
                $stateMachine->transition($reservation, ReservationStatus::Returned, $request->driver, "Retorno de vehículo confirmado.");
            }
        }

        return $request;
    }
}
