<?php

namespace App\Enums;

/**
 * Estado operativo del vehículo. Ver docs/02_BUSINESS_RULES.md (BR-V02).
 * Nota: la disponibilidad real se calcula por rango de fechas, no solo por este estado.
 */
enum VehicleStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Rented = 'rented';
    case Maintenance = 'maintenance';
    case Blocked = 'blocked';
    case OutOfService = 'out_of_service';

    /**
     * Estados que impiden que el vehículo sea rentable (independiente de reservas).
     *
     * @return list<self>
     */
    public static function nonRentable(): array
    {
        return [self::Maintenance, self::Blocked, self::OutOfService];
    }
}
