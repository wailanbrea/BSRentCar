<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeWebhookHandler;
use App\Services\Payments\PayPalWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook endpoints para proveedores de pago. Ver docs/17_PAYMENT_PROVIDERS.md.
 * Sin middleware de auth — la validación es por firma del proveedor.
 */
use App\Exceptions\InvalidPayPalSignatureException;

class WebhookController extends Controller
{
    public function __construct(
        private readonly StripeWebhookHandler $stripeHandler,
        private readonly PayPalWebhookHandler $paypalHandler,
    ) {
    }

    /**
     * POST /api/v1/payments/webhooks/stripe
     * Procesa eventos de Stripe. Siempre retorna 200 (excepto firma inválida → 400).
     * Idempotente: procesar el mismo evento dos veces no causa duplicados.
     */
    public function handleStripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');
        $webhookSecret = config('rentcar.stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::error('Stripe webhook secret not configured.');

            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        try {
            $this->stripeHandler->handleWebhook($payload, $signature, $webhookSecret);

            return response()->json(['status' => 'ok']);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature invalid.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Throwable $e) {
            // Logear pero retornar 200 para que Stripe no reintente (el error es nuestro).
            Log::error('Stripe webhook processing error.', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'ok']);
        }
    }

    /**
     * POST /api/v1/payments/webhooks/paypal
     * Procesa eventos de PayPal. Siempre retorna 200 (excepto firma inválida → 400).
     */
    public function handlePaypal(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();
        $webhookId = config('rentcar.paypal.webhook_id');

        if (! $webhookId) {
            Log::error('PayPal webhook ID not configured.');

            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        try {
            $this->paypalHandler->handleWebhook($headers, $payload, $webhookId);

            return response()->json(['status' => 'ok']);
        } catch (InvalidPayPalSignatureException $e) {
            Log::warning('PayPal webhook signature invalid.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('PayPal webhook processing error.', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'ok']);
        }
    }
}
