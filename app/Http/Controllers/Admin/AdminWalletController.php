<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Operaciones administrativas sobre las billeteras de los clientes.
 */
class AdminWalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * POST /api/v1/admin/customers/{id}/wallet/adjust
     * Realiza un ajuste manual (acreditación o débito, positivo o negativo) sobre la billetera de un cliente.
     */
    public function adjust(Request $request, int $customerId): JsonResponse
    {
        $request->validate([
            'amount'      => ['required', 'numeric', 'not_in:0'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $customer = Customer::findOrFail($customerId);
        $wallet = $this->walletService->getWallet($customer);

        $amountStr = number_format((float) $request->input('amount'), 2, '.', '');

        $transaction = $this->walletService->adjust(
            wallet: $wallet,
            amount: $amountStr,
            description: $request->input('description'),
            createdBy: $request->user()
        );

        return response()->json([
            'message'         => 'Ajuste de billetera realizado correctamente.',
            'balance'         => $wallet->fresh()->balance,
            'transaction_id'  => $transaction->id,
            'amount'          => $transaction->amount,
            'type'            => $transaction->type,
            'description'     => $transaction->description,
        ]);
    }
}
