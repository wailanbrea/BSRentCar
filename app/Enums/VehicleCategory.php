<?php

namespace App\Enums;

/**
 * Categoría del vehículo. Ver docs/04_DATABASE_SCHEMA.md (#6).
 */
enum VehicleCategory: string
{
    case Economy = 'economy';
    case Sedan = 'sedan';
    case Suv = 'suv';
    case Luxury = 'luxury';
    case Van = 'van';
    case Pickup = 'pickup';
}
