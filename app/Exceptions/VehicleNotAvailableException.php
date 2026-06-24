<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * El vehículo no está disponible para el rango solicitado (doble reserva / bloqueo).
 * Ver docs/10_RESERVATIONS_FLOW.md (BR-R02).
 */
class VehicleNotAvailableException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'El vehículo no está disponible para las fechas seleccionadas.',
            'code' => 'VEHICLE_NOT_AVAILABLE',
        ], 409);
    }
}
