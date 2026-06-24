<?php

namespace App\Services\Payments;

/**
 * DTO inmutable para la respuesta normalizada de cualquier proveedor de pago.
 * Ver docs/17_PAYMENT_PROVIDERS.md.
 */
readonly class PaymentGatewayResponse
{
    public function __construct(
        public bool $success,
        public string $status,
        public string $provider,
        public ?string $providerPaymentId,
        public ?string $providerOrderId,
        public ?string $providerCaptureId,
        public int $amountCents,
        public string $currency,
        public bool $requiresAction,
        public ?string $clientSecret,
        public ?string $actionUrl,
        public array $raw,
        public ?string $errorCode,
        public ?string $errorMessage,
    ) {}

    /**
     * Fábrica para respuestas exitosas.
     */
    public static function success(
        string $status,
        string $provider,
        int $amountCents,
        string $currency = 'DOP',
        ?string $providerPaymentId = null,
        ?string $providerOrderId = null,
        ?string $providerCaptureId = null,
        bool $requiresAction = false,
        ?string $clientSecret = null,
        ?string $actionUrl = null,
        array $raw = [],
    ): self {
        return new self(
            success: true,
            status: $status,
            provider: $provider,
            providerPaymentId: $providerPaymentId,
            providerOrderId: $providerOrderId,
            providerCaptureId: $providerCaptureId,
            amountCents: $amountCents,
            currency: $currency,
            requiresAction: $requiresAction,
            clientSecret: $clientSecret,
            actionUrl: $actionUrl,
            raw: $raw,
            errorCode: null,
            errorMessage: null,
        );
    }

    /**
     * Fábrica para respuestas fallidas.
     */
    public static function failure(
        string $provider,
        string $errorCode,
        string $errorMessage,
        int $amountCents = 0,
        string $currency = 'DOP',
        ?string $providerPaymentId = null,
        array $raw = [],
    ): self {
        return new self(
            success: false,
            status: 'failed',
            provider: $provider,
            providerPaymentId: $providerPaymentId,
            providerOrderId: null,
            providerCaptureId: null,
            amountCents: $amountCents,
            currency: $currency,
            requiresAction: false,
            clientSecret: null,
            actionUrl: null,
            raw: $raw,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }
}
