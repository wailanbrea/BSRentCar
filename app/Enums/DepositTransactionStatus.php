<?php

namespace App\Enums;

/**
 * Estados de la transacción de depósito. Ver docs/02_BUSINESS_RULES.md (§5).
 */
enum DepositTransactionStatus: string
{
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Released = 'released';
    case Failed = 'failed';
}
