<?php

namespace App\Services\Payments;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Stripe implementation of PaymentGatewayInterface.
 *
 * All monetary values at this layer are expressed in **cents** (int) to avoid
 * floating-point rounding issues.  The higher-level PaymentService is
 * responsible for converting between BCMath strings and cents.
 *
 * Every creation call includes an idempotency key so that retries against
 * the Stripe API are safe.
 *
 * @see \App\Services\Payments\PaymentGatewayInterface
 */
class StripePaymentGateway implements PaymentGatewayInterface
{
    private StripeClient $stripe;

    public function __construct(string $secretKey)
    {
        $this->stripe = new StripeClient($secretKey);
    }

    // -------------------------------------------------------------------------
    //  PaymentIntent – create / capture / cancel
    // -------------------------------------------------------------------------

    /**
     * Create a Stripe PaymentIntent.
     *
     * @param  array{
     *     amount_cents: int,
     *     currency: string,
     *     capture_method?: string,
     *     metadata?: array<string, string>,
     *     idempotency_key: string,
     *     customer?: string|null,
     *     payment_method?: string|null,
     * }  $data
     */
    public function createPayment(array $data): PaymentGatewayResponse
    {
        try {
            $params = [
                'amount'         => $data['amount_cents'],
                'currency'       => strtolower($data['currency'] ?? 'usd'),
                'capture_method' => $data['capture_method'] ?? 'automatic',
                'metadata'       => $data['metadata'] ?? [],
            ];

            if (! empty($data['customer'])) {
                $params['customer'] = $data['customer'];
            }

            if (! empty($data['payment_method'])) {
                $params['payment_method']      = $data['payment_method'];
                $params['confirm']             = true;
                $params['automatic_payment_methods'] = ['enabled' => true];
            } else {
                $params['automatic_payment_methods'] = ['enabled' => true];
            }

            $intent = $this->stripe->paymentIntents->create(
                $params,
                ['idempotency_key' => $data['idempotency_key']],
            );

            return PaymentGatewayResponse::success(
                status: $this->mapIntentStatus($intent->status),
                provider: 'stripe',
                amountCents: $intent->amount,
                currency: strtoupper($intent->currency),
                providerPaymentId: $intent->id,
                requiresAction: $this->mapIntentStatus($intent->status) === 'requires_action',
                clientSecret: $intent->client_secret,
                raw: $intent->toArray(),
            );
        } catch (ApiErrorException $e) {
            return $this->failureFromException($e);
        }
    }

    /**
     * Capture an authorized PaymentIntent (full or partial).
     *
     * @param  int|null  $amountCents  If provided, performs a partial capture.
     */
    public function capturePayment(string $providerPaymentId, ?int $amountCents = null): PaymentGatewayResponse
    {
        try {
            $params = [];

            if ($amountCents !== null) {
                $params['amount_to_capture'] = $amountCents;
            }

            $intent = $this->stripe->paymentIntents->capture(
                $providerPaymentId,
                $params,
            );

            return PaymentGatewayResponse::success(
                status: $this->mapIntentStatus($intent->status),
                provider: 'stripe',
                amountCents: $intent->amount,
                currency: strtoupper($intent->currency),
                providerPaymentId: $intent->id,
                raw: $intent->toArray(),
            );
        } catch (ApiErrorException $e) {
            return $this->failureFromException($e);
        }
    }

    /**
     * Cancel / void an uncaptured PaymentIntent.
     */
    public function cancelPayment(string $providerPaymentId): PaymentGatewayResponse
    {
        try {
            $intent = $this->stripe->paymentIntents->cancel($providerPaymentId);

            return PaymentGatewayResponse::success(
                status: 'cancelled',
                provider: 'stripe',
                amountCents: $intent->amount,
                currency: strtoupper($intent->currency),
                providerPaymentId: $intent->id,
                raw: $intent->toArray(),
            );
        } catch (ApiErrorException $e) {
            return $this->failureFromException($e);
        }
    }

    // -------------------------------------------------------------------------
    //  Refunds
    // -------------------------------------------------------------------------

    /**
     * Create a refund against a PaymentIntent.
     *
     * @param  int  $amountCents  Amount to refund in cents.
     */
    public function refundPayment(string $providerPaymentId, int $amountCents): PaymentGatewayResponse
    {
        try {
            $refund = $this->stripe->refunds->create(
                [
                    'payment_intent' => $providerPaymentId,
                    'amount'         => $amountCents,
                ],
                ['idempotency_key' => 'refund_' . $providerPaymentId . '_' . $amountCents . '_' . time()],
            );

            return PaymentGatewayResponse::success(
                status: $refund->status,          // succeeded | pending | failed
                provider: 'stripe',
                amountCents: $refund->amount,
                currency: strtoupper($refund->currency),
                providerPaymentId: $refund->id,
                raw: $refund->toArray(),
            );
        } catch (ApiErrorException $e) {
            return $this->failureFromException($e);
        }
    }

    // -------------------------------------------------------------------------
    //  Customers & payment methods
    // -------------------------------------------------------------------------

    /**
     * Create a Stripe Customer.
     *
     * @param  array{email?: string, name?: string, metadata?: array<string, string>}  $data
     */
    public function createCustomer(array $data): PaymentGatewayResponse
    {
        try {
            $customer = $this->stripe->customers->create([
                'email'    => $data['email'] ?? null,
                'name'     => $data['name'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            return PaymentGatewayResponse::success(
                status: 'created',
                provider: 'stripe',
                amountCents: 0,
                currency: 'DOP',
                providerPaymentId: $customer->id,
                raw: $customer->toArray(),
            );
        } catch (ApiErrorException $e) {
            return $this->failureFromException($e);
        }
    }

    /**
     * Attach a PaymentMethod to a Customer and set it as the default.
     */
    public function savePaymentMethod(string $providerCustomerId, string $paymentMethodId): PaymentGatewayResponse
    {
        try {
            // Attach the payment method to the customer.
            $this->stripe->paymentMethods->attach(
                $paymentMethodId,
                ['customer' => $providerCustomerId],
            );

            // Set as default invoice payment method.
            $customer = $this->stripe->customers->update(
                $providerCustomerId,
                [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId,
                    ],
                ],
            );

            return PaymentGatewayResponse::success(
                status: 'attached',
                provider: 'stripe',
                amountCents: 0,
                currency: 'DOP',
                providerPaymentId: $paymentMethodId,
                raw: $customer->toArray(),
            );
        } catch (ApiErrorException $e) {
            return $this->failureFromException($e);
        }
    }

    // -------------------------------------------------------------------------
    //  Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Map Stripe PaymentIntent status to a normalised internal status string.
     *
     * Stripe statuses → internal:
     *   requires_payment_method, requires_confirmation, requires_action → requires_action
     *   processing                                                      → processing
     *   requires_capture                                                → authorized
     *   succeeded                                                       → paid
     *   canceled                                                        → cancelled
     */
    private function mapIntentStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'requires_payment_method',
            'requires_confirmation',
            'requires_action'        => 'requires_action',
            'processing'             => 'processing',
            'requires_capture'       => 'authorized',
            'succeeded'              => 'paid',
            'canceled'               => 'cancelled',
            default                  => $stripeStatus,
        };
    }

    /**
     * Build a failure response from a Stripe API exception.
     */
    private function failureFromException(ApiErrorException $e): PaymentGatewayResponse
    {
        $body = $e->getJsonBody();
        $error = $body['error'] ?? [];

        return PaymentGatewayResponse::failure(
            provider: 'stripe',
            errorCode: $error['code'] ?? $e->getStripeCode() ?? 'stripe_error',
            errorMessage: $error['message'] ?? $e->getMessage(),
            raw: $body ?? ['exception' => $e->getMessage()],
        );
    }
}
