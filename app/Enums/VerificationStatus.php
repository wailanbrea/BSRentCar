<?php

namespace App\Enums;

/**
 * Estado de verificación del cliente. Ver docs/02_BUSINESS_RULES.md (BR-C04).
 */
enum VerificationStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
