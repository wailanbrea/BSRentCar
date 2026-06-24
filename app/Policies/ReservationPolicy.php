<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * Autorización de reservas. Ver docs/11_SECURITY.md (§6).
 */
class ReservationPolicy
{
    /** Staff/admin pueden ver/gestionar cualquier reserva. */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['admin', 'staff'])) {
            return true;
        }

        return null;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $this->owns($user, $reservation);
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $this->owns($user, $reservation);
    }

    private function owns(User $user, Reservation $reservation): bool
    {
        return $reservation->customer?->user_id === $user->id;
    }
}
