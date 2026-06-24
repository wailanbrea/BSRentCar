<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * El cliente no cumple los requisitos para rentar (edad/licencia).
 * Ver docs/02_BUSINESS_RULES.md (BR-C08/C09) y docs/10_RESERVATIONS_FLOW.md (gate 4.0).
 */
class CustomerNotEligibleException extends RuntimeException
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(private array $reasons)
    {
        parent::__construct('Customer not eligible to rent.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'No cumples los requisitos para reservar.',
            'code' => 'CUSTOMER_NOT_ELIGIBLE',
            'reasons' => $this->reasons,
        ], 422);
    }
}
