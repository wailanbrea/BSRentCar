<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;

/**
 * Validates incoming Stripe webhooks and dispatches to PaymentService.
 *
 * Signature verification is performed using \Stripe\Webhook::constructEvent()
 * which will throw SignatureVerificationException if the Stripe-Signature
 * header does not match.
 *
 * All state mutations are handled inside PaymentService, which is itself
 * idempotent (safe to receive the same event more than once).
 */
class StripeWebhookHandler
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Process a raw Stripe webhook payload.
     *
     * @param  string  $payload        Raw request body (JSON string).
     * @param  string  $signature      Value of the `Stripe-Signature` HTTP header.
     * @param  string  $webhookSecret  Webhook endpoint signing secret (whsec_…).
     *
     * @throws SignatureVerificationException  If the signature is invalid.
     * @throws UnexpectedValueException        If the JSON payload cannot be parsed.
     */
    public function handleWebhook(string $payload, string $signature, string $webhookSecret): void
    {
        // This will throw if the payload or signature is invalid.
        $event = Webhook::constructEvent($payload, $signature, $webhookSecret);

        $eventType = $event->type;
        $object    = $event->data->object; // Typically a PaymentIntent or Charge

        Log::channel('payments')->info('Stripe webhook received', [
            'event_id'   => $event->id,
            'event_type' => $eventType,
        ]);

        match ($eventType) {
            'payment_intent.succeeded' => $this->onPaymentSucceeded($object),

            'payment_intent.payment_failed' => $this->onPaymentFailed($object),

            'payment_intent.amount_capturable_updated' => $this->onPaymentAuthorized($object),

            'charge.refunded' => $this->onChargeRefunded($object),

            'payment_intent.canceled' => $this->onPaymentCancelled($object),

            default => Log::channel('payments')->notice('Unhandled Stripe event type', [
                'event_id'   => $event->id,
                'event_type' => $eventType,
            ]),
        };
    }

    // -------------------------------------------------------------------------
    //  Event handlers
    // -------------------------------------------------------------------------

    private function onPaymentSucceeded(object $intent): void
    {
        $this->paymentService->handlePaymentSucceeded(
            providerPaymentId: $intent->id,
            raw: $intent->toArray(),
        );
    }

    private function onPaymentFailed(object $intent): void
    {
        $lastError = $intent->last_payment_error;

        $this->paymentService->handlePaymentFailed(
            providerPaymentId: $intent->id,
            errorCode: $lastError?->code ?? null,
            errorMessage: $lastError?->message ?? null,
            raw: $intent->toArray(),
        );
    }

    private function onPaymentAuthorized(object $intent): void
    {
        $this->paymentService->handlePaymentAuthorized(
            providerPaymentId: $intent->id,
            raw: $intent->toArray(),
        );
    }

    private function onChargeRefunded(object $charge): void
    {
        // A charge belongs to a PaymentIntent; extract the PI id.
        $providerPaymentId = $charge->payment_intent;

        $this->paymentService->handleRefunded(
            providerPaymentId: $providerPaymentId,
            raw: $charge->toArray(),
        );
    }

    private function onPaymentCancelled(object $intent): void
    {
        $this->paymentService->handlePaymentCancelled(
            providerPaymentId: $intent->id,
            raw: $intent->toArray(),
        );
    }
}
