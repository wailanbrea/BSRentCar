<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use App\Services\Payments\PaymentService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints para que el cliente consulte y recargue su billetera digital.
 */
class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly CustomerService $customerService,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * GET /api/v1/customer/wallet
     * Obtiene el balance y el historial de transacciones de la billetera.
     */
    public function show(Request $request): JsonResponse
    {
        $customer = $this->customerService->createForUser($request->user());
        $wallet = $this->walletService->getWallet($customer);

        // Cargamos las transacciones ordenadas descendentemente por fecha
        $transactions = $wallet->transactions()
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($tx) {
                return [
                    'id'            => $tx->id,
                    'type'          => $tx->type,
                    'amount'        => $tx->amount,
                    'balance_after' => $tx->balance_after,
                    'description'   => $tx->description,
                    'created_at'    => $tx->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'balance'      => $wallet->balance,
            'currency'     => $wallet->currency,
            'status'       => $wallet->status,
            'transactions' => $transactions,
        ]);
    }

    /**
     * POST /api/v1/customer/wallet/topup
     * Inicia una recarga de saldo utilizando Stripe o PayPal.
     */
    public function topup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'   => ['required', 'numeric', 'min:1.00'],
            'provider' => ['required', 'string', 'in:stripe,paypal'],
        ]);

        $customer = $this->customerService->createForUser($request->user());

        $amountStr = number_format((float) $validated['amount'], 2, '.', '');

        // Iniciamos el pago de tipo recarga
        $result = $this->paymentService->initiatePayment(
            reservation: null,
            paymentType: 'wallet_topup',
            provider: $validated['provider'],
            captureMethod: 'automatic',
            customAmount: $amountStr,
            customerId: $customer->id
        );

        return response()->json([
            'approve_url'       => $result['client_secret'], // Redirection for PayPal or Client Secret for Stripe
            'payment_intent_id' => $result['payment_intent_id'],
            'amount'            => $result['amount'],
            'currency'          => $result['currency'],
            'status'            => $result['status'],
            'payment_id'        => $result['payment_id'],
        ]);
    }
}
