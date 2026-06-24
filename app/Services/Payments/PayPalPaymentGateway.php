<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal REST API v2 implementation of PaymentGatewayInterface.
 *
 * This implementation makes direct HTTP requests to the PayPal API, avoiding
 * obsolete SDK packages. It supports OAuth 2.0 client credentials authentication
 * with caching, Order creation (intent CAPTURE or AUTHORIZE), Capture, Void (cancel),
 * and webhook signature verification.
 *
 * PayPal amounts are formatted as decimal strings (e.g. "12.34").
 */
class PayPalPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly bool $sandbox = true,
    ) {}

    // -------------------------------------------------------------------------
    //  PaymentGatewayInterface methods
    // -------------------------------------------------------------------------

    public function createPayment(array $data): PaymentGatewayResponse
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = $this->getApiUrl('/v2/checkout/orders');

            $amountVal = number_format($data['amount_cents'] / 100, 2, '.', '');
            $currency = strtoupper($data['currency'] ?? 'USD');
            $intent = strtoupper($data['capture_method'] ?? 'automatic') === 'MANUAL' ? 'AUTHORIZE' : 'CAPTURE';

            $payload = [
                'intent' => $intent,
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => $amountVal,
                        ],
                        'custom_id' => $data['metadata']['reservation_id'] ?? null,
                        'description' => "RentCar Reservation #" . ($data['metadata']['reservation_id'] ?? ''),
                    ]
                ],
                'application_context' => [
                    'return_url' => route('api.payments.paypal.confirm', ['status' => 'success']),
                    'cancel_url' => route('api.payments.paypal.confirm', ['status' => 'cancel']),
                ]
            ];

            // PayPal idempotency is controlled via the PayPal-Request-Id header.
            $response = Http::withHeaders([
                'PayPal-Request-Id' => $data['idempotency_key'],
            ])
            ->withToken($accessToken)
            ->post($url, $payload);

            if ($response->failed()) {
                return $this->failureResponse($response, $data['amount_cents'], $currency);
            }

            $order = $response->json();
            $links = $order['links'] ?? [];
            $approveUrl = null;

            foreach ($links as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approveUrl = $link['href'];
                    break;
                }
            }

            return PaymentGatewayResponse::success(
                status: $this->mapOrderStatus($order['status'] ?? 'CREATED'),
                provider: 'paypal',
                amountCents: $data['amount_cents'],
                currency: $currency,
                providerPaymentId: $order['id'] ?? null,
                providerOrderId: $order['id'] ?? null,
                requiresAction: ! empty($approveUrl),
                clientSecret: $approveUrl, // Set URL here as clientSecret for UX redirection
                actionUrl: $approveUrl,
                raw: $order
            );
        } catch (\Throwable $e) {
            Log::channel('payments')->error('PayPal createPayment exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PaymentGatewayResponse::failure(
                provider: 'paypal',
                errorCode: 'paypal_exception',
                errorMessage: $e->getMessage(),
                amountCents: $data['amount_cents'] ?? 0,
                currency: $data['currency'] ?? 'USD'
            );
        }
    }

    public function capturePayment(string $providerPaymentId, ?int $amountCents = null): PaymentGatewayResponse
    {
        try {
            $accessToken = $this->getAccessToken();

            // Detect if this is an authorization ID (we capture auths) or order ID (we capture orders).
            // PayPal Order IDs usually start with '5O', Auth IDs usually start with '8SR' or '9' etc.
            // But we can check if it is already captured or try to capture order first.
            // If amountCents is provided or it's an authorization ID, we perform authorization capture.
            $isOrder = str_starts_with($providerPaymentId, '5O') || str_starts_with($providerPaymentId, 'EC-');

            if ($isOrder && $amountCents === null) {
                // Capture order
                $url = $this->getApiUrl("/v2/checkout/orders/{$providerPaymentId}/capture");
                $response = Http::withToken($accessToken)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url);
            } else {
                // Capture authorization
                $url = $this->getApiUrl("/v2/payments/authorizations/{$providerPaymentId}/capture");
                $payload = [];
                if ($amountCents !== null) {
                    $payload['amount'] = [
                        'currency_code' => 'USD',
                        'value' => number_format($amountCents / 100, 2, '.', ''),
                    ];
                }
                $response = Http::withToken($accessToken)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);
            }

            if ($response->failed()) {
                return $this->failureResponse($response, $amountCents ?? 0);
            }

            $result = $response->json();
            $status = $result['status'] ?? 'COMPLETED';

            return PaymentGatewayResponse::success(
                status: $this->mapCaptureStatus($status),
                provider: 'paypal',
                amountCents: $amountCents ?? 0,
                providerPaymentId: $result['id'] ?? $providerPaymentId,
                providerCaptureId: $result['id'] ?? null,
                raw: $result
            );
        } catch (\Throwable $e) {
            Log::channel('payments')->error('PayPal capturePayment exception', [
                'error' => $e->getMessage(),
            ]);

            return PaymentGatewayResponse::failure(
                provider: 'paypal',
                errorCode: 'paypal_exception',
                errorMessage: $e->getMessage()
            );
        }
    }

    public function cancelPayment(string $providerPaymentId): PaymentGatewayResponse
    {
        try {
            $accessToken = $this->getAccessToken();

            // Void authorization or order
            $isOrder = str_starts_with($providerPaymentId, '5O') || str_starts_with($providerPaymentId, 'EC-');

            if ($isOrder) {
                // We cannot void a captured/completed order, but we can void authorizations.
                // If it's a raw order ID, we call the order details first or default.
                $url = $this->getApiUrl("/v2/checkout/orders/{$providerPaymentId}");
                $orderResponse = Http::withToken($accessToken)->get($url);
                if ($orderResponse->successful()) {
                    $order = $orderResponse->json();
                    // Extract authorization ID if available
                    $authId = $order['purchase_units'][0]['payments']['authorizations'][0]['id'] ?? null;
                    if ($authId) {
                        $providerPaymentId = $authId;
                        $isOrder = false;
                    }
                }
            }

            if ($isOrder) {
                // No direct void endpoint for orders in PayPal if not authorized. We just return success.
                return PaymentGatewayResponse::success(
                    status: 'cancelled',
                    provider: 'paypal',
                    amountCents: 0,
                    providerPaymentId: $providerPaymentId
                );
            }

            $url = $this->getApiUrl("/v2/payments/authorizations/{$providerPaymentId}/void");
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url);

            if ($response->failed()) {
                return $this->failureResponse($response, 0);
            }

            return PaymentGatewayResponse::success(
                status: 'cancelled',
                provider: 'paypal',
                amountCents: 0,
                providerPaymentId: $providerPaymentId
            );
        } catch (\Throwable $e) {
            Log::channel('payments')->error('PayPal cancelPayment exception', [
                'error' => $e->getMessage(),
            ]);

            return PaymentGatewayResponse::failure(
                provider: 'paypal',
                errorCode: 'paypal_exception',
                errorMessage: $e->getMessage()
            );
        }
    }

    public function refundPayment(string $providerPaymentId, int $amountCents): PaymentGatewayResponse
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = $this->getApiUrl("/v2/payments/captures/{$providerPaymentId}/refund");

            $payload = [
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($amountCents / 100, 2, '.', ''),
                ]
            ];

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->failed()) {
                return $this->failureResponse($response, $amountCents);
            }

            $refund = $response->json();
            return PaymentGatewayResponse::success(
                status: strtolower($refund['status'] ?? 'completed') === 'completed' ? 'refunded' : 'failed',
                provider: 'paypal',
                amountCents: $amountCents,
                providerPaymentId: $refund['id'] ?? null,
                raw: $refund
            );
        } catch (\Throwable $e) {
            Log::channel('payments')->error('PayPal refundPayment exception', [
                'error' => $e->getMessage(),
            ]);

            return PaymentGatewayResponse::failure(
                provider: 'paypal',
                errorCode: 'paypal_exception',
                errorMessage: $e->getMessage()
            );
        }
    }

    public function createCustomer(array $data): PaymentGatewayResponse
    {
        // PayPal does not require customer registration for simple API payments.
        // We return a simulated success.
        return PaymentGatewayResponse::success(
            status: 'created',
            provider: 'paypal',
            amountCents: 0,
            providerPaymentId: 'pp_cust_' . uniqid()
        );
    }

    public function savePaymentMethod(string $providerCustomerId, string $paymentMethodId): PaymentGatewayResponse
    {
        // Vaulting is deferred for simple flow.
        return PaymentGatewayResponse::success(
            status: 'attached',
            provider: 'paypal',
            amountCents: 0,
            providerPaymentId: $paymentMethodId
        );
    }

    // -------------------------------------------------------------------------
    //  Webhook signature verification
    // -------------------------------------------------------------------------

    /**
     * Verify the PayPal webhook signature by calling PayPal's verification API.
     */
    public function verifyWebhookSignature(array $headers, string $rawPayload, string $webhookId): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = $this->getApiUrl('/v1/notifications/verify-webhook-signature');

            // Header keys are case-insensitive.
            $transmissionId   = $headers['paypal-transmission-id'][0] ?? $headers['paypal-transmission-id'] ?? '';
            $transmissionTime = $headers['paypal-transmission-time'][0] ?? $headers['paypal-transmission-time'] ?? '';
            $certUrl          = $headers['paypal-cert-url'][0] ?? $headers['paypal-cert-url'] ?? '';
            $authAlgo         = $headers['paypal-auth-algo'][0] ?? $headers['paypal-auth-algo'] ?? '';
            $transmissionSig  = $headers['paypal-transmission-sig'][0] ?? $headers['paypal-transmission-sig'] ?? '';

            $payload = [
                'transmission_id'   => $transmissionId,
                'transmission_time' => $transmissionTime,
                'cert_url'          => $certUrl,
                'auth_algo'         => $authAlgo,
                'transmission_sig'  => $transmissionSig,
                'webhook_id'        => $webhookId,
                'webhook_event'     => json_decode($rawPayload, true),
            ];

            $response = Http::withToken($accessToken)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::channel('payments')->warning('PayPal signature verification call failed', [
                    'body' => $response->body(),
                ]);
                return false;
            }

            $status = $response->json('verification_status');
            return strtoupper($status) === 'SUCCESS';
        } catch (\Throwable $e) {
            Log::channel('payments')->error('PayPal webhook verification exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // -------------------------------------------------------------------------
    //  Internal / Private helpers
    // -------------------------------------------------------------------------

    private function getAccessToken(): string
    {
        return Cache::remember('paypal_access_token', 3500, function () {
            $url = $this->getApiUrl('/v1/oauth2/token');

            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post($url, [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                throw new \RuntimeException("PayPal OAuth failed: " . $response->body());
            }

            return $response->json('access_token');
        });
    }

    private function getApiUrl(string $path): string
    {
        $domain = $this->sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        return $domain . '/' . ltrim($path, '/');
    }

    private function mapOrderStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'CREATED'   => 'pending',
            'APPROVED'  => 'processing',
            'COMPLETED' => 'paid',
            'VOIDED'    => 'cancelled',
            default     => 'pending',
        };
    }

    private function mapCaptureStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'COMPLETED' => 'paid',
            'DECLINED',
            'FAILED'    => 'failed',
            'PENDING'   => 'processing',
            default     => 'paid',
        };
    }

    private function failureResponse($response, int $amountCents = 0, string $currency = 'USD'): PaymentGatewayResponse
    {
        $body = $response->json() ?? [];
        $errorCode = $body['name'] ?? 'paypal_error';
        $errorMessage = $body['message'] ?? $response->body();

        return PaymentGatewayResponse::failure(
            provider: 'paypal',
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            amountCents: $amountCents,
            currency: $currency,
            raw: $body
        );
    }
}
