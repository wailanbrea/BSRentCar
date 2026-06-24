<?php

namespace App\Services;

use App\Enums\DepositTransactionStatus;
use App\Enums\DepositTransactionType;
use App\Enums\PaymentStatus;
use App\Models\DepositTransaction;
use App\Models\Reservation;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para gestionar el ciclo de vida del depósito de seguridad.
 */
class DepositService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentGatewayFactory $gatewayFactory
    ) {}

    /**
     * Crea un hold (retención de autorización) para el depósito de seguridad de una reserva.
     */
    public function createHold(Reservation $reservation, string $provider): DepositTransaction
    {
        $result = $this->paymentService->initiatePayment(
            reservation: $reservation,
            paymentType: 'deposit',
            provider: $provider,
            captureMethod: 'manual'
        );

        $status = DepositTransactionStatus::Authorized; // Por defecto asumimos autorizado si requiere redirigir o procesar
        $expiresAt = now()->addDays(7); // Hold estándar de 7 días

        return DepositTransaction::create([
            'reservation_id'     => $reservation->id,
            'customer_id'        => $reservation->customer_id,
            'provider'           => $provider,
            'provider_reference' => $result['payment_intent_id'],
            'type'               => DepositTransactionType::Hold,
            'amount'             => $result['amount'],
            'currency'           => $result['currency'],
            'status'             => $status,
            'reason'             => 'Security deposit hold',
            'expires_at'         => $expiresAt,
        ]);
    }

    /**
     * Captura total o parcialmente el depósito retenido.
     */
    public function capture(DepositTransaction $deposit, string $amount, string $reason): DepositTransaction
    {
        return DB::transaction(function () use ($deposit, $amount, $reason) {
            if ($deposit->status !== DepositTransactionStatus::Authorized) {
                throw new \RuntimeException("Deposit is not in authorized status.");
            }

            // Convertir amount a centavos
            $amountCents = (int) bcmul($amount, '100', 0);

            // Obtener el gateway y capturar
            $gateway = $this->gatewayFactory->make($deposit->provider);
            $response = $gateway->capturePayment($deposit->provider_reference, $amountCents);

            if (! $response->success) {
                throw new \RuntimeException("Gateway capture failed: " . $response->errorMessage);
            }

            // Actualizar el depósito original
            $deposit->update([
                'status'          => DepositTransactionStatus::Captured,
                'captured_amount' => $amount,
            ]);

            // Crear la transacción de captura
            $type = bccomp($amount, $deposit->amount, 2) === 0
                ? DepositTransactionType::Capture
                : DepositTransactionType::PartialCapture;

            return DepositTransaction::create([
                'reservation_id'     => $deposit->reservation_id,
                'customer_id'        => $deposit->customer_id,
                'provider'           => $deposit->provider,
                'provider_reference' => $deposit->provider_reference,
                'type'               => $type,
                'amount'             => $deposit->amount,
                'currency'           => $deposit->currency,
                'status'             => DepositTransactionStatus::Captured,
                'reason'             => $reason,
                'captured_amount'    => $amount,
            ]);
        });
    }

    /**
     * Libera completamente el depósito retenido.
     */
    public function release(DepositTransaction $deposit, string $reason): DepositTransaction
    {
        return DB::transaction(function () use ($deposit, $reason) {
            if ($deposit->status !== DepositTransactionStatus::Authorized) {
                throw new \RuntimeException("Deposit is not in authorized status.");
            }

            // Obtener el gateway y liberar/void
            $gateway = $this->gatewayFactory->make($deposit->provider);
            $response = $gateway->cancelPayment($deposit->provider_reference);

            if (! $response->success) {
                throw new \RuntimeException("Gateway release failed: " . $response->errorMessage);
            }

            // Actualizar el depósito original
            $deposit->update([
                'status' => DepositTransactionStatus::Released,
            ]);

            // Crear la transacción de liberación
            return DepositTransaction::create([
                'reservation_id'     => $deposit->reservation_id,
                'customer_id'        => $deposit->customer_id,
                'provider'           => $deposit->provider,
                'provider_reference' => $deposit->provider_reference,
                'type'               => DepositTransactionType::Release,
                'amount'             => $deposit->amount,
                'currency'           => $deposit->currency,
                'status'             => DepositTransactionStatus::Released,
                'reason'             => $reason,
            ]);
        });
    }
}
