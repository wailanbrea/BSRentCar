<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

use App\Exceptions\InvalidPayPalSignatureException;

/**
 * Handles incoming PayPal webhooks, verifies signature, and updates PaymentService.
 */
class PayPalWebhookHandler
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PayPalPaymentGateway $gateway,
    ) {}

    /**
     * Parse and verify a PayPal webhook event.
     *
     * @param  array   $headers    HTTP headers array.
     * @param  string  $payload    Raw request body JSON string.
     * @param  string  $webhookId  Configured webhook ID.
     */
    public function handleWebhook(array $headers, string $payload, string $webhookId): void
    {
        // 1. Verify webhook signature cryptographically
        $verified = $this->gateway->verifyWebhookSignature($headers, $payload, $webhookId);

        if (! $verified) {
            Log::channel('payments')->warning('PayPal webhook signature verification failed.');
            throw new InvalidPayPalSignatureException('Invalid PayPal webhook signature.');
        }

        $event = json_decode($payload, true);
        $eventType = $event['event_type'] ?? null;
        $resource = $event['resource'] ?? [];

        Log::channel('payments')->info('PayPal webhook received', [
            'event_id'   => $event['id'] ?? null,
            'event_type' => $eventType,
        ]);

        switch ($eventType) {
            case 'CHECKOUT.ORDER.APPROVED':
                $this->onOrderApproved($resource);
                break;

            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->onCaptureCompleted($resource);
                break;

            case 'PAYMENT.CAPTURE.DENIED':
                $this->onCaptureDenied($resource);
                break;

            case 'PAYMENT.AUTHORIZATION.CREATED':
                $this->onAuthorizationCreated($resource);
                break;

            case 'PAYMENT.AUTHORIZATION.VOIDED':
                $this->onAuthorizationVoided($resource);
                break;

            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->onCaptureRefunded($resource);
                break;

            default:
                Log::channel('payments')->notice('Unhandled PayPal event type', [
                    'event_type' => $eventType,
                ]);
                break;
        }
    }

    // -------------------------------------------------------------------------
    //  Event Handlers
    // -------------------------------------------------------------------------

    private function onOrderApproved(array $resource): void
    {
        // An approved order can be captured dynamically if needed, or left pending.
        Log::channel('payments')->info('PayPal order approved by payer', [
            'order_id' => $resource['id'] ?? null,
        ]);
    }

    private function onCaptureCompleted(array $resource): void
    {
        $paymentId = $resource['custom_id'] ?? null;
        $payment = $paymentId ? Payment::find($paymentId) : null;

        if ($payment) {
            $payment->update([
                'provider_capture_id' => $resource['id'] ?? null,
            ]);

            $this->paymentService->handlePaymentSucceeded(
                providerPaymentId: $payment->provider_payment_id,
                raw: $resource
            );
        } else {
            Log::channel('payments')->warning('PayPal capture completed: payment record not found', [
                'custom_id' => $paymentId,
            ]);
        }
    }

    private function onCaptureDenied(array $resource): void
    {
        $paymentId = $resource['custom_id'] ?? null;
        $payment = $paymentId ? Payment::find($paymentId) : null;

        if ($payment) {
            $this->paymentService->handlePaymentFailed(
                providerPaymentId: $payment->provider_payment_id,
                errorCode: 'PAYMENT_DENIED',
                errorMessage: 'PayPal capture was denied.',
                raw: $resource
            );
        }
    }

    private function onAuthorizationCreated(array $resource): void
    {
        $paymentId = $resource['custom_id'] ?? null;
        $payment = $paymentId ? Payment::find($paymentId) : null;

        if ($payment) {
            $payment->update([
                'provider_capture_id' => $resource['id'] ?? null, // Auth ID
            ]);

            $this->paymentService->handlePaymentAuthorized(
                providerPaymentId: $payment->provider_payment_id,
                raw: $resource
            );
        }
    }

    private function onAuthorizationVoided(array $resource): void
    {
        $paymentId = $resource['custom_id'] ?? null;
        $payment = $paymentId ? Payment::find($paymentId) : null;

        if ($payment) {
            $this->paymentService->handlePaymentCancelled(
                providerPaymentId: $payment->provider_payment_id,
                raw: $resource
            );
        }
    }

    private function onCaptureRefunded(array $resource): void
    {
        $paymentId = $resource['custom_id'] ?? null;
        $payment = $paymentId ? Payment::find($paymentId) : null;

        if ($payment) {
            $this->paymentService->handleRefunded(
                providerPaymentId: $payment->provider_payment_id,
                raw: $resource
            );
        }
    }
}
