<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\CustomerService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de pago con Stripe para clientes. Ver docs/06_API_CONTRACTS.md.
 */
class StripePaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CustomerService $customers,
    ) {
    }

    /**
     * POST /api/v1/payments/stripe/create-intent
     * Crea un PaymentIntent de Stripe y devuelve el client_secret para el frontend.
     */
    public function createIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'payment_type'   => ['required', 'string', 'in:rent,deposit'],
            'save_method'    => ['sometimes', 'boolean'],
        ]);

        $reservation = Reservation::findOrFail($validated['reservation_id']);

        // Verificar ownership: la reserva pertenece al customer autenticado.
        $customer = $this->customers->createForUser($request->user());
        if ($reservation->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'This reservation does not belong to you.',
                'code'    => 'FORBIDDEN',
            ], 403);
        }

        // Verificar que la reserva está en estado pagable.
        if ($reservation->reservation_status !== ReservationStatus::PendingPayment) {
            return response()->json([
                'message' => 'Reservation is not in a payable state.',
                'code'    => 'NOT_PAYABLE',
            ], 409);
        }

        // Determinar capture_method: rent=automatic, deposit=manual (hold/authorize).
        $captureMethod = $validated['payment_type'] === 'deposit' ? 'manual' : 'automatic';

        $result = $this->paymentService->initiatePayment(
            $reservation,
            $validated['payment_type'],
            $captureMethod,
        );

        return response()->json([
            'client_secret'     => $result['client_secret'],
            'payment_intent_id' => $result['payment_intent_id'],
            'amount'            => $result['amount'],
            'currency'          => $result['currency'],
            'status'            => $result['status'],
            'payment_id'        => $result['payment_id'],
        ]);
    }

    /**
     * POST /api/v1/payments/stripe/confirm
     * Consulta de estado para UX inmediata. El webhook es source of truth.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        $payment = $this->paymentService->getPaymentByProviderId(
            $validated['payment_intent_id'],
        );

        if (! $payment) {
            return response()->json([
                'message' => 'Payment not found.',
                'code'    => 'NOT_FOUND',
            ], 404);
        }

        // Verificar ownership.
        $customer = $this->customers->createForUser($request->user());
        if ($payment->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'This payment does not belong to you.',
                'code'    => 'FORBIDDEN',
            ], 403);
        }

        return response()->json([
            'status'     => $payment->status->value,
            'payment_id' => $payment->id,
        ]);
    }
}
