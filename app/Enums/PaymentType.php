<?php

namespace App\Enums;

/**
 * Tipo de pago / cargo. Ver docs/09_PAYMENTS_WALLET.md (§3).
 */
enum PaymentType: string
{
    case Rent = 'rent';
    case Deposit = 'deposit';
    case DepositCapture = 'deposit_capture';
    case Penalty = 'penalty';
    case WalletTopup = 'wallet_topup';
    case Refund = 'refund';
}
