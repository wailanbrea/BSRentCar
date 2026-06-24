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
 * Endpoints de pago con PayPal para clientes. Ver docs/17_PAYMENT_PROVIDERS.md.
 */
class PayPalPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CustomerService $customers,
    ) {}

    /**
     * POST /api/v1/payments/paypal/create-intent
     * Crea una orden en PayPal y devuelve la URL de aprobación para el cliente.
     */
    public function createIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'payment_type'   => ['required', 'string', 'in:rent,deposit'],
        ]);

        $reservation = Reservation::findOrFail($validated['reservation_id']);

        // Verificar propiedad: la reserva pertenece al customer autenticado.
        $customer = $this->customers->createForUser($request->user());
        if ($reservation->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'This reservation does not belong to you.',
                'code'    => 'FORBIDDEN',
            ], 403);
        }

        // Verificar que la reserva está en un estado pagable.
        if ($reservation->reservation_status !== ReservationStatus::PendingPayment) {
            return response()->json([
                'message' => 'Reservation is not in a payable state.',
                'code'    => 'NOT_PAYABLE',
            ], 409);
        }

        // Determinar capture_method: rent=automatic (CAPTURE), deposit=manual (AUTHORIZE).
        $captureMethod = $validated['payment_type'] === 'deposit' ? 'manual' : 'automatic';

        $result = $this->paymentService->initiatePayment(
            $reservation,
            $validated['payment_type'],
            'paypal',
            $captureMethod,
        );

        return response()->json([
            'approve_url'       => $result['client_secret'], // Redirection URL
            'payment_intent_id' => $result['payment_intent_id'],
            'amount'            => $result['amount'],
            'currency'          => $result['currency'],
            'status'            => $result['status'],
            'payment_id'        => $result['payment_id'],
        ]);
    }

    /**
     * POST /api/v1/payments/paypal/confirm
     * Consulta el estado del pago local por ID de orden.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string'],
        ]);

        $payment = $this->paymentService->getPaymentByProviderId($validated['order_id']);

        if (! $payment) {
            return response()->json([
                'message' => 'Payment not found.',
                'code'    => 'NOT_FOUND',
            ], 404);
        }

        // Verificar propiedad.
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

    /**
     * GET /api/v1/payments/paypal/confirm-redirect
     * Captura el retorno de PayPal (exitoso o cancelado).
     */
    public function confirmRedirect(Request $request): JsonResponse
    {
        return response()->json([
            'message'  => 'PayPal redirect captured.',
            'status'   => $request->query('status'),
            'order_id' => $request->query('token'),
        ]);
    }
}
