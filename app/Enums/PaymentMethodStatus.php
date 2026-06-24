<?php

namespace App\Enums;

/**
 * Estado de un método de pago guardado. Ver docs/09_PAYMENTS_WALLET.md (§3).
 */
enum PaymentMethodStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Removed = 'removed';
}
