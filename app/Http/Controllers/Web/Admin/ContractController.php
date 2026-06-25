<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Reservation;
use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contracts)
    {
    }

    public function index(): View
    {
        $contracts = Contract::with('reservation')->latest()->paginate(20);

        return view('admin.contracts.index', compact('contracts'));
    }

    public function generate(Reservation $reservation): RedirectResponse
    {
        try {
            $this->contracts->generateContract($reservation, request()->user());
        } catch (\Throwable $e) {
            return back()->withErrors(['contrato' => 'No se pudo generar el contrato: '.$e->getMessage()]);
        }

        return back()->with('status', 'Contrato generado.');
    }

    public function download(Contract $contract): BinaryFileResponse
    {
        return response()->download($this->contracts->getContractPath($contract));
    }
}
