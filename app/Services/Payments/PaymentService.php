<?php

namespace App\Services\Payments;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates payment lifecycle: creation, webhook handling, refunds.
 *
 * Money is stored and manipulated as BCMath strings (scale 2) throughout this
 * service.  Conversion to/from integer cents only happens at the gateway
 * boundary via toCents() / fromCents().
 *
 * Every webhook handler is **idempotent**: receiving the same Stripe event
 * twice will not produce duplicate side-effects.
 *
 * @see \App\Services\Payments\PaymentGatewayInterface
 * @see \App\Services\ReservationService::markAsPaid()
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly ReservationService $reservationService,
        private readonly WalletService $walletService,
    ) {}

    // =========================================================================
    //  Public API – initiate payments
    // =========================================================================

    /**
     * Start a new payment flow for a reservation.
     *
     * @param  string       $paymentType     A PaymentType value (e.g. 'rent', 'deposit').
     * @param  string       $provider        The payment provider ('stripe', 'paypal').
     * @param  string|null  $captureMethod   'automatic' (default) or 'manual' (authorize-only).
     * @return array{
     *     client_secret: string|null,
     *     payment_intent_id: string|null,
     *     amount: string,
     *     currency: string,
     *     status: string,
     *     payment_id: int,
     * }
     *
     * @throws \RuntimeException  If the gateway call fails.
     */
    public function initiatePayment(
        ?Reservation $reservation,
        string $paymentType,
        string $provider,
        ?string $captureMethod = 'automatic',
        ?string $customAmount = null,
        ?int $customerId = null,
    ): array {
        $type = PaymentType::from($paymentType);

        if ($reservation) {
            $amount = $customAmount ?? $this->resolveRemainingAmount($reservation, $type);
            $customerIdVal = $reservation->customer_id;
            $currencyVal = $reservation->currency ?? 'USD';
        } else {
            if ($customAmount === null) {
                throw new \InvalidArgumentException("Amount is required for top-ups.");
            }
            if ($customerId === null) {
                throw new \InvalidArgumentException("Customer ID is required.");
            }
            $amount = $customAmount;
            $customerIdVal = $customerId;
            $currencyVal = 'USD';
        }

        return DB::transaction(function () use ($reservation, $type, $amount, $captureMethod, $provider, $customerIdVal, $currencyVal) {
            $idempotencyKey = (string) Str::uuid();

            // 1. Persist Payment record -----------------------------------------
            $payment = Payment::create([
                'reservation_id'      => $reservation?->id,
                'customer_id'         => $customerIdVal,
                'provider'            => $provider,
                'provider_payment_id' => null,  // filled after gateway call
                'amount'              => $amount,
                'currency'            => $currencyVal,
                'status'              => PaymentStatus::Pending->value,
                'payment_type'        => $type->value,
                'idempotency_key'     => $idempotencyKey,
                'metadata'            => [],
            ]);

            // 2. Persist PaymentAttempt -----------------------------------------
            $attempt = PaymentAttempt::create([
                'payment_id'     => $payment->id,
                'reservation_id' => $reservation?->id,
                'customer_id'    => $customerIdVal,
                'provider'       => $provider,
                'provider_reference' => null,
                'amount'         => $amount,
                'status'         => PaymentAttemptStatus::Initiated->value,
            ]);

            // Resolve gateway instance dynamically from Factory
            $gateway = $this->gatewayFactory->make($provider);

            // 3. Call gateway --------------------------------------------
            $response = $gateway->createPayment([
                'amount_cents'    => $this->toCents($amount),
                'currency'        => $payment->currency,
                'capture_method'  => $captureMethod,
                'metadata'        => [
                    'reservation_id' => $reservation ? (string) $reservation->id : null,
                    'customer_id'    => (string) $customerIdVal,
                    'payment_type'   => $type->value,
                    'payment_id'     => (string) $payment->id,
                ],
                'idempotency_key' => $idempotencyKey,
            ]);

            // 4. Handle gateway failure -----------------------------------------
            if (! $response->success) {
                $payment->update(['status' => PaymentStatus::Failed->value]);
                $attempt->update([
                    'status'        => PaymentAttemptStatus::Failed->value,
                    'error_code'    => $response->errorCode,
                    'error_message' => $response->errorMessage,
                ]);

                Log::channel('payments')->error('Payment gateway failure', [
                    'payment_id' => $payment->id,
                    'error_code' => $response->errorCode,
                    'error_msg'  => $response->errorMessage,
                ]);

                throw new \RuntimeException(
                    "Payment gateway error: {$response->errorCode} – {$response->errorMessage}"
                );
            }

            // 5. Update records with provider info ------------------------------
            $providerPaymentId = $response->providerPaymentId;
            $gatewayStatus     = $response->status;  // normalised by gateway

            $internalStatus = $this->mapGatewayStatusToPayment($gatewayStatus);

            $payment->update([
                'provider_payment_id' => $providerPaymentId,
                'status'              => $internalStatus->value,
                'metadata'            => $response->raw,
            ]);

            $attemptStatus = $gatewayStatus === 'requires_action'
                ? PaymentAttemptStatus::RequiresAction
                : PaymentAttemptStatus::Initiated;

            $attempt->update([
                'provider_reference' => $providerPaymentId,
                'status'             => $attemptStatus->value,
            ]);

            // 6. Return client-facing data --------------------------------------
            $clientSecret = $response->clientSecret;

            return [
                'client_secret'     => $clientSecret,
                'payment_intent_id' => $providerPaymentId,
                'amount'            => $amount,
                'currency'          => $payment->currency,
                'status'            => $internalStatus->value,
                'payment_id'        => $payment->id,
            ];
        });
    }

    // =========================================================================
    //  Webhook handlers (idempotent)
    // =========================================================================

    /**
     * Handle a successful payment (payment_intent.succeeded).
     *
     * @param  array<string, mixed>  $raw  Raw Stripe event object data.
     */
    public function handlePaymentSucceeded(string $providerPaymentId, array $raw): void
    {
        $payment = $this->getPaymentByProviderId($providerPaymentId);

        if (! $payment) {
            Log::channel('payments')->warning('handlePaymentSucceeded: payment not found', [
                'provider_payment_id' => $providerPaymentId,
            ]);
            return;
        }

        // Idempotent guard – already in a terminal success state.
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($payment, $raw) {
            $payment->update([
                'status'   => PaymentStatus::Paid->value,
                'paid_at'  => now(),
                'metadata' => array_merge($payment->metadata ?? [], ['webhook_succeeded' => $raw]),
            ]);

            // Mark latest attempt as succeeded.
            $this->updateLatestAttempt($payment, PaymentAttemptStatus::Succeeded);

            // Domain side-effect: mark reservation as paid (atomic availability re-check).
            $paymentType = $payment->payment_type;

            if ($paymentType === PaymentType::Rent && $payment->reservation_id) {
                $reservation = Reservation::find($payment->reservation_id);
                if ($reservation && $this->isFullyPaid($reservation, PaymentType::Rent)) {
                    $this->reservationService->markAsPaid($reservation);
                }
            }

            if ($paymentType === PaymentType::WalletTopup) {
                $customer = \App\Models\Customer::find($payment->customer_id);
                if ($customer) {
                    $wallet = $this->walletService->getWallet($customer);
                    $this->walletService->credit(
                        wallet: $wallet,
                        amount: $payment->amount,
                        type: 'credit',
                        description: "Top-up via " . ucfirst($payment->provider),
                        reference: $payment
                    );
                }
            }
        });
    }

    /**
     * Handle a failed payment (payment_intent.payment_failed).
     */
    public function handlePaymentFailed(
        string $providerPaymentId,
        ?string $errorCode,
        ?string $errorMessage,
        array $raw,
    ): void {
        $payment = $this->getPaymentByProviderId($providerPaymentId);

        if (! $payment) {
            Log::channel('payments')->warning('handlePaymentFailed: payment not found', [
                'provider_payment_id' => $providerPaymentId,
            ]);
            return;
        }

        // Idempotent guard – don't overwrite terminal states.
        if (in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Failed], true)) {
            return;
        }

        DB::transaction(function () use ($payment, $errorCode, $errorMessage, $raw) {
            $payment->update([
                'status'   => PaymentStatus::Failed->value,
                'metadata' => array_merge($payment->metadata ?? [], ['webhook_failed' => $raw]),
            ]);

            $attempt = $this->latestAttempt($payment);
            if ($attempt) {
                $attempt->update([
                    'status'        => PaymentAttemptStatus::Failed->value,
                    'error_code'    => $errorCode,
                    'error_message' => $errorMessage,
                ]);
            }
        });
    }

    /**
     * Handle an authorized (capturable) payment (payment_intent.amount_capturable_updated).
     */
    public function handlePaymentAuthorized(string $providerPaymentId, array $raw): void
    {
        $payment = $this->getPaymentByProviderId($providerPaymentId);

        if (! $payment) {
            Log::channel('payments')->warning('handlePaymentAuthorized: payment not found', [
                'provider_payment_id' => $providerPaymentId,
            ]);
            return;
        }

        // Idempotent guard.
        if ($payment->status === PaymentStatus::Authorized) {
            return;
        }

        $payment->update([
            'status'   => PaymentStatus::Authorized->value,
            'metadata' => array_merge($payment->metadata ?? [], ['webhook_authorized' => $raw]),
        ]);
    }

    /**
     * Handle a refund (charge.refunded).
     *
     * Determines whether this is a full or partial refund by comparing the
     * refunded amount from the charge with the original Payment amount.
     */
    public function handleRefunded(string $providerPaymentId, array $raw): void
    {
        $payment = $this->getPaymentByProviderId($providerPaymentId);

        if (! $payment) {
            Log::channel('payments')->warning('handleRefunded: payment not found', [
                'provider_payment_id' => $providerPaymentId,
            ]);
            return;
        }

        $amountRefundedCents = $raw['amount_refunded'] ?? 0;
        $totalCents          = $this->toCents($payment->amount);

        $newStatus = $amountRefundedCents >= $totalCents
            ? PaymentStatus::Refunded
            : PaymentStatus::PartiallyRefunded;

        $payment->update([
            'status'   => $newStatus->value,
            'metadata' => array_merge($payment->metadata ?? [], ['webhook_refunded' => $raw]),
        ]);
    }

    /**
     * Handle a cancelled payment (payment_intent.canceled).
     */
    public function handlePaymentCancelled(string $providerPaymentId, array $raw): void
    {
        $payment = $this->getPaymentByProviderId($providerPaymentId);

        if (! $payment) {
            Log::channel('payments')->warning('handlePaymentCancelled: payment not found', [
                'provider_payment_id' => $providerPaymentId,
            ]);
            return;
        }

        // Idempotent guard.
        if ($payment->status === PaymentStatus::Cancelled) {
            return;
        }

        $payment->update([
            'status'   => PaymentStatus::Cancelled->value,
            'metadata' => array_merge($payment->metadata ?? [], ['webhook_cancelled' => $raw]),
        ]);
    }

    // =========================================================================
    //  Query helpers
    // =========================================================================

    /**
     * Find a Payment by its Stripe provider_payment_id.
     */
    public function getPaymentByProviderId(string $providerPaymentId): ?Payment
    {
        return Payment::where('provider_payment_id', $providerPaymentId)->first();
    }

    // =========================================================================
    //  Money helpers (BCMath ↔ cents)
    // =========================================================================

    /**
     * Convert a BCMath string (e.g. "149.99") to integer cents (14999).
     */
    private function toCents(string $amount): int
    {
        // Multiply by 100, round, cast.
        return (int) bcmul($amount, '100', 0);
    }

    /**
     * Convert integer cents (14999) to a BCMath string ("149.99").
     */
    private function fromCents(int $cents): string
    {
        return bcdiv((string) $cents, '100', 2);
    }

    // =========================================================================
    //  Internal helpers
    // =========================================================================

    /**
     * Resolve the amount to charge based on payment type.
     */
    private function resolveAmount(Reservation $reservation, PaymentType $type): string
    {
        return match ($type) {
            PaymentType::Deposit,
            PaymentType::DepositCapture => $reservation->deposit_amount,
            default                     => $reservation->total_amount,
        };
    }

    /**
     * Map gateway normalised status string to PaymentStatus enum.
     */
    private function mapGatewayStatusToPayment(string $gatewayStatus): PaymentStatus
    {
        return match ($gatewayStatus) {
            'requires_action' => PaymentStatus::RequiresAction,
            'processing'      => PaymentStatus::Processing,
            'authorized'      => PaymentStatus::Authorized,
            'paid'            => PaymentStatus::Paid,
            'cancelled'       => PaymentStatus::Cancelled,
            default           => PaymentStatus::Pending,
        };
    }

    /**
     * Update the latest PaymentAttempt for a given Payment.
     */
    private function updateLatestAttempt(Payment $payment, PaymentAttemptStatus $status): void
    {
        $attempt = $this->latestAttempt($payment);
        $attempt?->update(['status' => $status->value]);
    }

    /**
     * Get the most recent PaymentAttempt for a Payment.
     */
    private function latestAttempt(Payment $payment): ?PaymentAttempt
    {
        return PaymentAttempt::where('payment_id', $payment->id)
            ->latest()
            ->first();
    }

    public function resolveRemainingAmount(Reservation $reservation, PaymentType $type): string
    {
        $targetAmount = $this->resolveAmount($reservation, $type);

        $paidAmountStr = '0.00';
        foreach ($reservation->payments()->where('payment_type', $type->value)->where('status', PaymentStatus::Paid->value)->get() as $payment) {
            $paidAmountStr = bcadd($paidAmountStr, $payment->amount, 2);
        }

        $remaining = bcsub($targetAmount, $paidAmountStr, 2);
        return bccomp($remaining, '0.00', 2) > 0 ? $remaining : '0.00';
    }

    public function isFullyPaid(Reservation $reservation, PaymentType $type): bool
    {
        $targetAmount = $this->resolveAmount($reservation, $type);

        $paidAmountStr = '0.00';
        foreach ($reservation->payments()->where('payment_type', $type->value)->where('status', PaymentStatus::Paid->value)->get() as $payment) {
            $paidAmountStr = bcadd($paidAmountStr, $payment->amount, 2);
        }

        return bccomp($paidAmountStr, $targetAmount, 2) >= 0;
    }
}
