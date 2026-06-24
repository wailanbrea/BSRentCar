<?php

namespace App\Enums;

/**
 * Estado del contrato de la reserva. Ver docs/04_DATABASE_SCHEMA.md (reservations).
 */
enum ContractStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Signed = 'signed';
}
