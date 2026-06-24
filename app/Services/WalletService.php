<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para gestionar billeteras y sus movimientos contables de forma atómica.
 */
class WalletService
{
    /**
     * Obtiene o crea la billetera del cliente de manera segura.
     */
    public function getWallet(Customer $customer): Wallet
    {
        return Wallet::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'currency' => 'USD',
                'balance'  => '0.00',
                'status'   => 'active',
            ]
        );
    }

    /**
     * Acredita fondos a la billetera (tipo: credit, refund, deposit_release, promo_credit, manual_adjustment).
     */
    public function credit(
        Wallet $wallet,
        string $amount,
        string $type,
        string $description,
        ?Model $reference = null,
        ?User $createdBy = null
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $type, $description, $reference, $createdBy) {
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($lockedWallet->status === 'frozen') {
                throw new \RuntimeException("Wallet is frozen and cannot receive credits.");
            }

            // Para prevenir errores de dirección, nos aseguramos que amount sea positivo para créditos estándar
            $absAmount = ltrim($amount, '-');

            $newBalance = bcadd($lockedWallet->balance, $absAmount, 2);

            $lockedWallet->update([
                'balance' => $newBalance,
            ]);

            return $lockedWallet->transactions()->create([
                'type'           => $type,
                'amount'         => $absAmount,
                'balance_after'  => $newBalance,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id'   => $reference ? $reference->getKey() : null,
                'description'    => $description,
                'created_by'     => $createdBy?->id,
            ]);
        });
    }

    /**
     * Debita fondos de la billetera (tipo: debit, deposit_hold, penalty_charge, manual_adjustment).
     */
    public function debit(
        Wallet $wallet,
        string $amount,
        string $type,
        string $description,
        ?Model $reference = null,
        ?User $createdBy = null
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $type, $description, $reference, $createdBy) {
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($lockedWallet->status === 'frozen') {
                throw new \RuntimeException("Wallet is frozen and cannot be debited.");
            }

            // Para prevenir errores de dirección, nos aseguramos que el amount sea positivo para el cálculo de resta
            $absAmount = ltrim($amount, '-');

            $newBalance = bcsub($lockedWallet->balance, $absAmount, 2);

            // La billetera no puede quedar negativa a menos que sea un manual_adjustment
            if (bccomp($newBalance, '0.00', 2) < 0 && $type !== 'manual_adjustment') {
                throw new \RuntimeException("Insufficient funds in wallet.");
            }

            $lockedWallet->update([
                'balance' => $newBalance,
            ]);

            // Guardamos el amount como positivo (el tipo de transacción define que resta en el mayor)
            return $lockedWallet->transactions()->create([
                'type'           => $type,
                'amount'         => $absAmount,
                'balance_after'  => $newBalance,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id'   => $reference ? $reference->getKey() : null,
                'description'    => $description,
                'created_by'     => $createdBy?->id,
            ]);
        });
    }

    /**
     * Realiza un ajuste manual del saldo (puede ser positivo o negativo, auditado).
     */
    public function adjust(
        Wallet $wallet,
        string $amount,
        string $description,
        ?User $createdBy = null
    ): WalletTransaction {
        // Si el monto es negativo, realizamos un débito que puede dejar la cuenta en negativo
        if (bccomp($amount, '0.00', 2) < 0) {
            $absAmount = ltrim($amount, '-');
            return $this->debit($wallet, $absAmount, 'manual_adjustment', $description, null, $createdBy);
        }

        // Si es positivo, es un crédito
        return $this->credit($wallet, $amount, 'manual_adjustment', $description, null, $createdBy);
    }

    /**
     * Reconcilia el saldo consolidado de la billetera con el historial de transacciones.
     * Retorna true si el saldo era correcto, false si hubo discrepancias que fueron corregidas.
     */
    public function reconcile(Wallet $wallet): bool
    {
        return DB::transaction(function () use ($wallet) {
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            $calculatedBalance = '0.00';
            $transactions = $lockedWallet->transactions()->orderBy('id')->get();

            foreach ($transactions as $tx) {
                if (in_array($tx->type, ['credit', 'refund', 'deposit_release', 'promo_credit'])) {
                    $calculatedBalance = bcadd($calculatedBalance, $tx->amount, 2);
                } elseif (in_array($tx->type, ['debit', 'deposit_hold', 'penalty_charge'])) {
                    $calculatedBalance = bcsub($calculatedBalance, $tx->amount, 2);
                } elseif ($tx->type === 'manual_adjustment') {
                    // Para manual_adjustment, determinamos si sumamos o restamos comparando balance_after
                    // Pero para hacerlo matemáticamente independiente, podemos revisar si el ajuste fue positivo o negativo.
                    // Si el balance después es menor que el balance anterior (o si se guardó como débito interno).
                    // Para simplificar: dado que debit() guarda debit manual_adjustment y credit() guarda credit manual_adjustment,
                    // podemos saber la dirección mirando si la transacción redujo el saldo.
                    // Para ser más directos, podemos guardar un indicador o usar la diferencia de balance_after.
                    // Alternativamente, puesto que debit() y credit() ya restan/suman el amount del saldo de la billetera,
                    // podemos mirar la transacción anterior en el bucle:
                    // $CalculatedBalance es el saldo justo antes de este manual_adjustment.
                    // Así que el cambio es: $tx->balance_after - $prevCalculatedBalance.
                    // Si el cambio es positivo, se sumó. Si es negativo, se restó.
                    // Esto es auto-explicativo y 100% exacto para cualquier ajuste!
                    $difference = bcsub($tx->balance_after, $calculatedBalance, 2);
                    $calculatedBalance = bcadd($calculatedBalance, $difference, 2);
                }
            }

            $matches = bccomp($lockedWallet->balance, $calculatedBalance, 2) === 0;

            if (! $matches) {
                $lockedWallet->update([
                    'balance' => $calculatedBalance,
                ]);
            }

            return $matches;
        });
    }
}
