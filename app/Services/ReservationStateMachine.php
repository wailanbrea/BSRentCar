<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use InvalidArgumentException;

/**
 * Máquina de estados de la reserva. Ver docs/10_RESERVATIONS_FLOW.md (§2).
 * Rechaza transiciones no permitidas y registra cada cambio en reservation_status_logs.
 */
class ReservationStateMachine
{
    /**
     * Transiciones permitidas: from => [to, ...].
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'draft' => ['pending_payment', 'cancelled', 'expired'],
        'pending_payment' => ['paid', 'cancelled', 'expired'],
        'paid' => ['confirmed', 'cancelled', 'refunded'],
        'confirmed' => ['in_preparation', 'contract_pending', 'cancelled', 'refunded'],
        'in_preparation' => ['contract_pending', 'cancelled'],
        'contract_pending' => ['contract_signed', 'cancelled'],
        'contract_signed' => ['delivery_assigned', 'cancelled'],
        'delivery_assigned' => ['delivered', 'no_show', 'cancelled'],
        'delivered' => ['active'],
        'active' => ['return_pending'],
        'return_pending' => ['returned'],
        'returned' => ['inspection_pending', 'completed'],
        'inspection_pending' => ['completed'],
        // Terminales:
        'completed' => [],
        'cancelled' => [],
        'refunded' => [],
        'no_show' => [],
        'expired' => [],
    ];

    public function canTransition(ReservationStatus $from, ReservationStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true);
    }

    /**
     * Aplica una transición validada y la registra. Lanza InvalidArgumentException si no es válida.
     */
    public function transition(
        Reservation $reservation,
        ReservationStatus $to,
        ?User $by = null,
        ?string $reason = null
    ): Reservation {
        $from = $reservation->reservation_status;

        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                "Transición inválida de '{$from->value}' a '{$to->value}'."
            );
        }

        $reservation->update(['reservation_status' => $to->value]);

        $reservation->statusLogs()->create([
            'from_status' => $from->value,
            'to_status' => $to->value,
            'changed_by' => $by?->id,
            'reason' => $reason,
        ]);

        return $reservation;
    }
}
