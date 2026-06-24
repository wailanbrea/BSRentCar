<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Reservation;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminContractController extends Controller
{
    public function __construct(protected readonly ContractService $contractService)
    {
    }

    /**
     * Genera (o regenera) el borrador del contrato para una reservación.
     */
    public function generate(Request $request, Reservation $reservation): ContractResource
    {
        try {
            $contract = $this->contractService->generateContract($reservation, $request->user());
            return new ContractResource($contract);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }
    }

    /**
     * Descarga el PDF del contrato.
     */
    public function download(Request $request, Reservation $reservation): BinaryFileResponse
    {
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
