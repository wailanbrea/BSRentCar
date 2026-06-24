<?php

namespace App\Enums;

/**
 * Estado de pago. Ver docs/09_PAYMENTS_WALLET.md (§3).
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case RequiresAction = 'requires_action';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
}
