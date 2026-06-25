<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

/**
 * Expira reservas en pending_payment cuyo hold venció, liberando el cupo.
 * Ver docs/10_RESERVATIONS_FLOW.md (BR-R10).
 */
class ExpireReservationHolds extends Command
{
    protected $signature = 'rentcar:expire-reservation-holds';

    protected $description = 'Expira las reservas pendientes de pago cuyo hold superó el tiempo configurado.';

    public function handle(ReservationService $reservations): int
    {
        $count = $reservations->expireStaleHolds();

        $this->info("Reservas expiradas: {$count}");

        return self::SUCCESS;
    }
}
