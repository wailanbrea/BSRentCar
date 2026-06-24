<?php

namespace App\Enums;

/**
 * Tipo de transmisión. Ver docs/04_DATABASE_SCHEMA.md (#6).
 */
enum Transmission: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';
}
