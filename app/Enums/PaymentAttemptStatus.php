<?php

namespace App\Enums;

/**
 * Estado de un intento de pago contra el proveedor. Ver docs/09_PAYMENTS_WALLET.md (§3).
 */
enum PaymentAttemptStatus: string
{
    case Initiated = 'initiated';
    case RequiresAction = 'requires_action';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
