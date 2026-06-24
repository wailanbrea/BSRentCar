<?php

namespace App\Console\Commands;

use App\Enums\DepositTransactionStatus;
use App\Models\DepositTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando de consola para alertar sobre depósitos retenidos que están próximos a expirar en Stripe/PayPal.
 */
class CheckExpiredDeposits extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'rentcar:check-expired-deposits';

    /**
     * Descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Detecta autorizaciones de depósitos próximas a expirar en las próximas 24 horas y alerta en logs/consola.';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(): int
    {
        $threshold = now()->addHours(24);

        // Buscar depósitos autorizados cuya fecha de expiración sea menor o igual al umbral
        $expiringSoon = DepositTransaction::where('status', DepositTransactionStatus::Authorized)
            ->where('expires_at', '<=', $threshold)
            ->get();

        if ($expiringSoon->isEmpty()) {
            $this->info("No expiring deposits found.");
            return Command::SUCCESS;
        }

        foreach ($expiringSoon as $deposit) {
            $message = sprintf(
                "Deposit #%d for Reservation #%d (Provider Reference: %s) is expiring on %s.",
                $deposit->id,
                $deposit->reservation_id,
                $deposit->provider_reference,
                $deposit->expires_at->toDateTimeString()
            );

            $this->warn($message);

            Log::channel('payments')->warning($message, [
                'deposit_id'         => $deposit->id,
                'reservation_id'     => $deposit->reservation_id,
                'provider_reference' => $deposit->provider_reference,
                'expires_at'         => $deposit->expires_at->toDateTimeString(),
            ]);
        }

        $this->info(sprintf("Checked deposits. Found %d expiring soon.", $expiringSoon->count()));

        return Command::SUCCESS;
    }
}
