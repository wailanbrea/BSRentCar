<?php

namespace App\Enums;

/**
 * Tipo de recogida/devolución. Ver docs/02_BUSINESS_RULES.md (BR-E01).
 */
enum PickupType: string
{
    case PickupPoint = 'pickup_point';
    case Home = 'home';
    case Office = 'office';
    case Airport = 'airport';
    case Hotel = 'hotel';
    case Custom = 'custom';
}
