<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepositTransaction;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Operaciones administrativas para capturar o liberar depósitos de seguridad de los clientes.
 */
class AdminDepositController extends Controller
{
    public function __construct(
        private readonly DepositService $depositService
    ) {}

    /**
     * POST /api/v1/admin/deposits/{id}/capture
     * Captura total o parcialmente el depósito retenido.
     */
    public function capture(Request $request, int $id): JsonResponse
    {
        $deposit = DepositTransaction::findOrFail($id);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $deposit->amount],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $amountStr = number_format((float) $request->input('amount'), 2, '.', '');

        $captureTransaction = $this->depositService->capture(
            deposit: $deposit,
            amount: $amountStr,
            reason: $request->input('reason')
        );

        return response()->json([
            'message'         => 'Depósito capturado correctamente.',
            'deposit_id'      => $deposit->id,
            'captured_amount' => $captureTransaction->captured_amount,
            'type'            => $captureTransaction->type->value,
            'status'          => $captureTransaction->status->value,
        ]);
    }

    /**
     * POST /api/v1/admin/deposits/{id}/release
     * Libera completamente el depósito de seguridad retenido.
     */
    public function release(Request $request, int $id): JsonResponse
    {
        $deposit = DepositTransaction::findOrFail($id);

        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $releaseTransaction = $this->depositService->release(
            deposit: $deposit,
            reason: $request->input('reason')
        );

        return response()->json([
            'message'    => 'Depósito liberado correctamente.',
            'deposit_id' => $deposit->id,
            'type'       => $releaseTransaction->type->value,
            'status'     => $releaseTransaction->status->value,
        ]);
    }
}
