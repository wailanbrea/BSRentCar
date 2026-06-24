<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\ReservationStatus;
use App\Models\Contract;
use App\Models\Reservation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractService
{
    /**
     * Genera un nuevo contrato (borrador/pendiente) para una reservación.
     */
    public function generateContract(Reservation $reservation, ?User $generatedBy = null): Contract
    {
        if (!in_array($reservation->reservation_status->value, ['confirmed', 'in_preparation'], true)) {
            throw new \DomainException("La reservación debe estar en estado 'confirmed' o 'in_preparation' para generar un contrato.");
        }

        if ($reservation->contract && $reservation->contract->status->value === 'signed') {
            throw new \DomainException("Ya existe un contrato firmado para esta reservación.");
        }

        return DB::transaction(function () use ($reservation, $generatedBy) {
            // Anular contrato anterior si existe
            if ($reservation->contract) {
                $reservation->contract->update(['status' => 'void']);
            }

            $number = 'CTR-' . $reservation->reservation_number . '-' . strtoupper(Str::random(4));

            $contract = new Contract([
                'reservation_id' => $reservation->id,
                'number' => $number,
                'file_path' => 'temporary',
                'status' => 'pending',
                'generated_by' => $generatedBy?->id,
            ]);
            $contract->save();

            $filePath = "contracts/contract_{$reservation->id}.pdf";
            $pdf = Pdf::loadView('pdf.contract', [
                'contract' => $contract,
                'reservation' => $reservation,
                'customer' => $reservation->customer,
            ]);

            Storage::disk('local')->put($filePath, $pdf->output());

            $contract->update([
                'file_path' => $filePath,
            ]);

            // Transicionar estado de la reserva
            $stateMachine = app(ReservationStateMachine::class);
            $stateMachine->transition($reservation, ReservationStatus::ContractPending, $generatedBy, "Contrato generado: {$number}");
            $reservation->update(['contract_status' => ContractStatus::Pending->value]);

            return $contract;
        });
    }

    /**
     * Firma el contrato de forma simple registrando metadatos y recalculando el hash del PDF final.
     */
    public function signContract(Contract $contract, string $printedName, string $ip, string $userAgent): Contract
    {
        if ($contract->status->value !== 'pending') {
            throw new \DomainException("El contrato no se encuentra en estado pendiente de firma.");
        }

        $reservation = $contract->reservation;

        return DB::transaction(function () use ($contract, $reservation, $printedName, $ip, $userAgent) {
            $fullPath = Storage::disk('local')->path($contract->file_path);
            if (!file_exists($fullPath)) {
                throw new \RuntimeException("El archivo de contrato no se encuentra en el almacenamiento.");
            }
            $hash = hash_file('sha256', $fullPath);

            $contract->update([
                'status' => 'signed',
                'signed_by_customer_at' => now(),
                'signature_meta' => [
                    'printed_name' => $printedName,
                    'ip' => $ip,
                    'ua' => $userAgent,
                    'hash' => $hash,
                ],
            ]);

            // Regenerar PDF para incrustar la sección de firma con metadatos
            $pdf = Pdf::loadView('pdf.contract', [
                'contract' => $contract,
                'reservation' => $reservation,
                'customer' => $reservation->customer,
            ]);
            Storage::disk('local')->put($contract->file_path, $pdf->output());

            // Transicionar estado de la reserva
            $stateMachine = app(ReservationStateMachine::class);
            $user = $reservation->customer->user;
            $stateMachine->transition($reservation, ReservationStatus::ContractSigned, $user, "Contrato firmado por el cliente.");
            $reservation->update(['contract_status' => ContractStatus::Signed->value]);

            return $contract;
        });
    }

    /**
     * Obtiene la ruta física del archivo PDF.
     */
    public function getContractPath(Contract $contract): string
    {
        return Storage::disk('local')->path($contract->file_path);
    }
}
