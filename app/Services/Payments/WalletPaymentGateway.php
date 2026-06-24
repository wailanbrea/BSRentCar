<?php

namespace App\Services\Payments;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\WalletService;

/**
 * Conexión del flujo contable de billetera con el patrón unificado de pasarelas de pago.
 */
class WalletPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function createPayment(array $data): PaymentGatewayResponse
    {
        try {
            $customerId = (int) $data['metadata']['customer_id'];
            $customer = Customer::findOrFail($customerId);
            $wallet = $this->walletService->getWallet($customer);

            $amount = bcdiv((string) $data['amount_cents'], '100', 2);
            $type = 'debit';
            $description = "Pago de Reserva #" . ($data['metadata']['reservation_id'] ?? '');

            // Realizar el débito en la billetera
            $transaction = $this->walletService->debit(
                $wallet,
                $amount,
                $type,
                $description,
                Reservation::find($data['metadata']['reservation_id'] ?? null)
            );

            return PaymentGatewayResponse::success(
                status: 'paid',
                provider: 'wallet',
                amountCents: $data['amount_cents'],
                currency: $data['currency'] ?? 'USD',
                providerPaymentId: 'wtx_' . $transaction->id,
                providerOrderId: 'wtx_' . $transaction->id,
                requiresAction: false,
                clientSecret: null,
                actionUrl: null,
                raw: $transaction->toArray()
            );
        } catch (\Throwable $e) {
            return PaymentGatewayResponse::failure(
                provider: 'wallet',
                errorCode: 'wallet_error',
                errorMessage: $e->getMessage(),
                amountCents: $data['amount_cents'] ?? 0,
                currency: $data['currency'] ?? 'USD'
            );
        }
    }

    public function capturePayment(string $providerPaymentId, ?int $amountCents = null): PaymentGatewayResponse
    {
        return PaymentGatewayResponse::success(
            status: 'paid',
            provider: 'wallet',
            amountCents: $amountCents ?? 0,
            providerPaymentId: $providerPaymentId
        );
    }

    public function cancelPayment(string $providerPaymentId): PaymentGatewayResponse
    {
        return PaymentGatewayResponse::success(
            status: 'cancelled',
            provider: 'wallet',
            amountCents: 0,
            providerPaymentId: $providerPaymentId
        );
    }

    public function refundPayment(string $providerPaymentId, int $amountCents): PaymentGatewayResponse
    {
        try {
            $payment = Payment::where('provider_payment_id', $providerPaymentId)->first();
            if (! $payment) {
                throw new \RuntimeException("Payment not found for reference: {$providerPaymentId}");
            }

            $customer = Customer::findOrFail($payment->customer_id);
            $wallet = $this->walletService->getWallet($customer);
            $amount = bcdiv((string) $amountCents, '100', 2);

            $transaction = $this->walletService->credit(
                $wallet,
                $amount,
                'refund',
                "Reembolso de pago {$providerPaymentId}",
                $payment->reservation
            );

            return PaymentGatewayResponse::success(
                status: 'refunded',
                provider: 'wallet',
                amountCents: $amountCents,
                providerPaymentId: 'wtx_' . $transaction->id,
                raw: $transaction->toArray()
            );
        } catch (\Throwable $e) {
            return PaymentGatewayResponse::failure(
                provider: 'wallet',
                errorCode: 'wallet_refund_error',
                errorMessage: $e->getMessage(),
                amountCents: $amountCents
            );
        }
    }

    public function createCustomer(array $data): PaymentGatewayResponse
    {
        return PaymentGatewayResponse::success(
            status: 'created',
            provider: 'wallet',
            amountCents: 0,
            providerPaymentId: 'w_cust_' . uniqid()
        );
    }

    public function savePaymentMethod(string $providerCustomerId, string $paymentMethodId): PaymentGatewayResponse
    {
        return PaymentGatewayResponse::success(
            status: 'attached',
            provider: 'wallet',
            amountCents: 0,
            providerPaymentId: $paymentMethodId
        );
    }
}
