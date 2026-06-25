<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\CustomerNotEligibleException;
use App\Exceptions\VehicleNotAvailableException;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lógica de reservas. Ver docs/10_RESERVATIONS_FLOW.md.
 * La revalidación de disponibilidad + transición a 'paid' es ATÓMICA (transacción + lockForUpdate).
 */
class ReservationService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly ReservationStateMachine $stateMachine,
    ) {
    }

    /**
     * Crea una reserva en estado pending_payment con su cotización.
     * Valida elegibilidad del cliente (edad/licencia) y disponibilidad (chequeo suave).
     *
     * @param  array<string, mixed>  $data
     */
    public function createForCustomer(Customer $customer, array $data): Reservation
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $start = CarbonImmutable::parse($data['start_datetime']);
        $end = CarbonImmutable::parse($data['end_datetime']);

        // Gate de elegibilidad (BR-C08/C09).
        $errors = $customer->rentalEligibilityErrors($start);
        if ($errors !== []) {
            throw new CustomerNotEligibleException($errors);
        }

        // Chequeo suave de disponibilidad (la validación dura ocurre al pagar).
        if (! $this->availability->isAvailable($vehicle, $start, $end)) {
            throw new VehicleNotAvailableException();
        }

        $quote = $this->pricing->quote($vehicle, $start, $end);

        $deliveryFee = '0.00';   // Fase 11 (entregas por zona/distancia).
        $insuranceFee = '0.00';  // Fase seguro (insurance_plans).
        $discount = '0.00';

        $taxable = $this->pricing->add($quote['base_price'], $deliveryFee, $insuranceFee, "-{$discount}");
        $tax = $this->pricing->tax($taxable);
        $total = $this->pricing->add($taxable, $tax);

        return DB::transaction(function () use ($customer, $vehicle, $start, $end, $data, $quote, $deliveryFee, $insuranceFee, $discount, $tax, $total) {
            $reservation = Reservation::create([
                'reservation_number' => $this->generateNumber(),
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'pickup_location_id' => $data['pickup_location_id'] ?? null,
                'return_location_id' => $data['return_location_id'] ?? null,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'pickup_type' => $data['pickup_type'] ?? 'office',
                'pickup_address' => $data['pickup_address'] ?? null,
                'return_type' => $data['return_type'] ?? null,
                'return_address' => $data['return_address'] ?? null,
                'base_price' => $quote['base_price'],
                'delivery_fee' => $deliveryFee,
                'insurance_fee' => $insuranceFee,
                'deposit_amount' => $quote['deposit_amount'],
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'currency' => $quote['currency'],
                'payment_status' => PaymentStatus::Pending->value,
                'reservation_status' => ReservationStatus::PendingPayment->value,
                'contract_status' => 'none',
            ]);

            $reservation->statusLogs()->create([
                'from_status' => null,
                'to_status' => ReservationStatus::PendingPayment->value,
                'changed_by' => $customer->user_id,
                'reason' => 'reservation created',
            ]);

            return $reservation;
        });
    }

    /**
     * Confirma el pago de la reserva de forma ATÓMICA con revalidación de disponibilidad.
     * Esta es la barrera anti-doble-reserva (docs/10_RESERVATIONS_FLOW.md §3, BR-R06).
     */
    public function markAsPaid(Reservation $reservation, ?User $by = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $by) {
            // Bloqueo pesimista sobre el vehículo.
            $vehicle = Vehicle::where('id', $reservation->vehicle_id)->lockForUpdate()->firstOrFail();

            // Revalidar disponibilidad ignorando la propia reserva.
            $available = $this->availability->isAvailable(
                $vehicle,
                $reservation->start_datetime,
                $reservation->end_datetime,
                $reservation->id,
            );

            if (! $available) {
                throw new VehicleNotAvailableException();
            }

            $reservation->update(['payment_status' => PaymentStatus::Paid->value]);

            return $this->stateMachine->transition(
                $reservation,
                ReservationStatus::Paid,
                $by,
                'payment confirmed',
            );
        });
    }

    public function confirm(Reservation $reservation, ?User $by = null): Reservation
    {
        return $this->stateMachine->transition($reservation, ReservationStatus::Confirmed, $by, 'confirmed');
    }

    public function cancel(Reservation $reservation, ?User $by = null, ?string $reason = null): Reservation
    {
        return $this->stateMachine->transition($reservation, ReservationStatus::Cancelled, $by, $reason ?? 'cancelled');
    }

    /**
     * Expira las reservas en pending_payment cuyo hold superó el tiempo configurado,
     * liberando el cupo. Ver docs/10_RESERVATIONS_FLOW.md (BR-R10) y config/rentcar.php.
     *
     * @return int Cantidad de reservas expiradas.
     */
    public function expireStaleHolds(): int
    {
        $minutes = (int) config('rentcar.reservation_hold_minutes', 30);
        $cutoff = now()->subMinutes($minutes);

        $stale = Reservation::query()
            ->where('reservation_status', ReservationStatus::PendingPayment->value)
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $reservation) {
            $this->stateMachine->transition(
                $reservation,
                ReservationStatus::Expired,
                null,
                'hold expired',
            );
        }

        return $stale->count();
    }

    private function generateNumber(): string
    {
        do {
            $number = 'RC-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (Reservation::where('reservation_number', $number)->exists());

        return $number;
    }
}
