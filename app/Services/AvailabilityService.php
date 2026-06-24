<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Disponibilidad por rango de fechas. Ver docs/10_RESERVATIONS_FLOW.md (§3).
 * Solape: new_start < existing_end AND new_end > existing_start.
 * Considera: estado del vehículo, bloqueos manuales y reservas en estados bloqueantes (BR-R08).
 */
class AvailabilityService
{
    /**
     * Restringe un query de vehículos a los disponibles en el rango dado.
     */
    public function filterAvailable(Builder $query, \DateTimeInterface $start, \DateTimeInterface $end): Builder
    {
        return $query
            ->whereNotIn('status', array_map(
                fn (VehicleStatus $s) => $s->value,
                VehicleStatus::nonRentable()
            ))
            ->whereDoesntHave('availabilityBlocks', fn (Builder $q) => $this->overlap($q, $start, $end))
            ->whereDoesntHave('reservations', function (Builder $q) use ($start, $end) {
                $q->whereIn('reservation_status', ReservationStatus::blockingValues());
                $this->overlap($q, $start, $end);
            });
    }

    /**
     * ¿Está el vehículo disponible en el rango? (estado + bloqueos + reservas bloqueantes).
     * $exceptReservationId permite ignorar la propia reserva al revalidar.
     */
    public function isAvailable(
        Vehicle $vehicle,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $exceptReservationId = null
    ): bool {
        if (in_array($vehicle->status, VehicleStatus::nonRentable(), true)) {
            return false;
        }

        $hasBlock = $vehicle->availabilityBlocks()
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();

        if ($hasBlock) {
            return false;
        }

        $hasReservation = $vehicle->reservations()
            ->whereIn('reservation_status', ReservationStatus::blockingValues())
            ->when($exceptReservationId, fn ($q) => $q->where('id', '!=', $exceptReservationId))
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();

        return ! $hasReservation;
    }

    /**
     * Cláusula de solape de rango sobre columnas start_datetime/end_datetime.
     */
    private function overlap(Builder $query, \DateTimeInterface $start, \DateTimeInterface $end): Builder
    {
        return $query
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start);
    }
}
