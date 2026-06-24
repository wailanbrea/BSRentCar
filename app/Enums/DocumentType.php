<?php

namespace App\Enums;

/**
 * Tipo de documento del cliente. Ver docs/04_DATABASE_SCHEMA.md (customer_documents).
 */
enum DocumentType: string
{
    case License = 'license';
    case IdFront = 'id_front';
    case IdBack = 'id_back';
    case ProofAddress = 'proof_address';
    case Other = 'other';
}
