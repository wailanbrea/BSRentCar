<?php

namespace App\Enums;

/**
 * Proveedores de pago soportados. Ver docs/09_PAYMENTS_WALLET.md (§3).
 */
enum PaymentProvider: string
{
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case Wallet = 'wallet';
    case Manual = 'manual';
}
