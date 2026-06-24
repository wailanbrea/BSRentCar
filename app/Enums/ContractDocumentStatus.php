<?php

namespace App\Enums;

/**
 * Estado del documento de contrato.
 */
enum ContractDocumentStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Signed = 'signed';
    case Void = 'void';
}
