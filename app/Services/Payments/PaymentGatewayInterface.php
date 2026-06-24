<?php

namespace App\Services\Payments;

/**
 * Contrato para todos los proveedores de pago. Ver docs/17_PAYMENT_PROVIDERS.md.
 * Los montos se manejan en centavos (int) para evitar errores de punto flotante.
 */
interface PaymentGatewayInterface
{
    /** Crear un cargo o intent de pago. */
    public function createPayment(array $data): PaymentGatewayResponse;

    /** Capturar un pago previamente autorizado (total o parcial). */
    public function capturePayment(string $providerPaymentId, ?int $amountCents = null): PaymentGatewayResponse;

    /** Cancelar/void un pago o autorización. */
    public function cancelPayment(string $providerPaymentId): PaymentGatewayResponse;

    /** Reembolsar (total o parcial). */
    public function refundPayment(string $providerPaymentId, int $amountCents): PaymentGatewayResponse;

    /** Crear un cliente en el proveedor. */
    public function createCustomer(array $data): PaymentGatewayResponse;

    /** Guardar un método de pago para uso futuro. */
    public function savePaymentMethod(string $providerCustomerId, string $paymentMethodId): PaymentGatewayResponse;
}
