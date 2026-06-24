<?php

namespace App\Enums;

/**
 * Tipos de transacción de depósito. Ver docs/02_BUSINESS_RULES.md (§5).
 */
enum DepositTransactionType: string
{
    case Hold = 'hold';
    case Capture = 'capture';
    case PartialCapture = 'partial_capture';
    case Release = 'release';
    case Charge = 'charge';
}
