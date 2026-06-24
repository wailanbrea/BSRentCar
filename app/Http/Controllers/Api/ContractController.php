<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\SignContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Reservation;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractController extends Controller
{
    public function __construct(protected readonly ContractService $contractService)
    {
    }

    /**
     * Obtiene el contrato de una reservación del cliente.
     */
    public function show(Request $request, Reservation $reservation): ContractResource
    {
        // Validar propiedad de la reservación
        if ($reservation->customer_id !== $request->user()->customer?->id) {
            abort(403, 'No autorizado para ver este contrato.');
        }

        $contract = $reservation->contract;

        if (!$contract) {
            abort(404, 'No se ha generado ningún contrato para esta reservación.');
        }

        return new ContractResource($contract);
    }

    /**
     * Firma digitalmente el contrato.
     */
    public function sign(SignContractRequest $request, Reservation $reservation): ContractResource
    {
        if ($reservation->customer_id !== $request->user()->customer?->id) {
            abort(403, 'No autorizado para firmar este contrato.');
        }

        $contract = $reservation->contract;

        if (!$contract) {
            abort(404, 'No existe un contrato para esta reservación.');
        }

        try {
            $updatedContract = $this->contractService->signContract(
                $contract,
                $request->input('printed_name'),
                $request->ip() ?? '127.0.0.1',
                $request->userAgent() ?? 'Unknown'
            );

            return new ContractResource($updatedContract);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }
    }

    /**
     * Descarga el contrato en PDF.
     */
    public function download(Request $request, Reservation $reservation): BinaryFileResponse
    {
        if ($reservation->customer_id !== $request->user()->customer?->id) {
            abort(403, 'No autorizado para descargar este contrato.');
        }

        $contract = $reservation->contract;

        if (!$contract) {
            abort(404, 'No se ha generado ningún contrato para esta reservación.');
        }

        $path = $this->contractService->getContractPath($contract);

        if (!file_exists($path)) {
            abort(404, 'El archivo del contrato no existe.');
        }

        return response()->download($path, "contrato_{$contract->number}.pdf");
    }
}
