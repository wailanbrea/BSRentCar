<?php

namespace App\Enums;

/**
 * Estado de revisión de un documento. Ver docs/02_BUSINESS_RULES.md (BR-C09).
 */
enum DocumentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
