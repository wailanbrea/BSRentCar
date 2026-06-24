<?php

namespace App\Services;

use App\Enums\VehicleInspectionType;
use App\Enums\ReservationStatus;
use App\Models\InspectionPhoto;
use App\Models\Reservation;
use App\Models\VehicleInspection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InspectionService
{
    /**
     * Registra una inspección de vehículo (inicial o final).
     */
    public function createInspection(Reservation $reservation, array $data, ?User $inspector = null): VehicleInspection
    {
        return DB::transaction(function () use ($reservation, $data, $inspector) {
            $type = $data['type'];

            // Validar estado de la reserva
            if ($type === VehicleInspectionType::Initial->value) {
                $allowedInitialStatuses = [
                    ReservationStatus::Confirmed,
                    ReservationStatus::InPreparation,
                    ReservationStatus::ContractPending,
                    ReservationStatus::ContractSigned,
                    ReservationStatus::DeliveryAssigned,
                    ReservationStatus::Delivered,
                ];
                if (!in_array($reservation->reservation_status, $allowedInitialStatuses, true)) {
                    throw new \DomainException("La reservación no se encuentra en un estado elegible para inspección inicial: " . $reservation->reservation_status->value);
                }
            } elseif ($type === VehicleInspectionType::Final->value) {
                $allowedFinalStatuses = [
                    ReservationStatus::Active,
                    ReservationStatus::ReturnPending,
                    ReservationStatus::Returned,
                    ReservationStatus::InspectionPending,
                ];
                if (!in_array($reservation->reservation_status, $allowedFinalStatuses, true)) {
                    throw new \DomainException("La reservación no se encuentra en un estado elegible para inspección final: " . $reservation->reservation_status->value);
                }
            } else {
                throw new \DomainException("Tipo de inspección inválido: " . $type);
            }

            // Crear el registro de la inspección
            $inspection = VehicleInspection::create([
                'reservation_id' => $reservation->id,
                'vehicle_id' => $reservation->vehicle_id,
                'type' => $type,
                'fuel_level' => $data['fuel_level'],
                'mileage' => $data['mileage'],
                'damages' => $data['damages'] ?? null,
                'notes' => $data['notes'] ?? null,
                'accepted_by_customer' => $data['accepted_by_customer'] ?? false,
                'inspector_id' => $inspector?->id,
                'inspected_at' => now(),
            ]);

            // Guardar firma digital si se proporciona (puede ser un string base64 o archivo)
            if (isset($data['signature'])) {
                $signaturePath = $this->storeSignature($inspection, $data['signature']);
                $inspection->update(['signature_path' => $signaturePath]);
            }

            // Guardar fotos iniciales si se adjuntan en el request
            if (isset($data['photos']) && is_array($data['photos'])) {
                foreach ($data['photos'] as $photoData) {
                    if (isset($photoData['file']) && $photoData['file'] instanceof UploadedFile) {
                        $this->uploadPhoto(
                            $inspection,
                            $photoData['file'],
                            $photoData['position'],
                            $photoData['note'] ?? null
                        );
                    }
                }
            }

            // Sincronizar estado de la reserva mediante la máquina de estados
            $stateMachine = app(ReservationStateMachine::class);

            if ($type === VehicleInspectionType::Initial->value) {
                if ($reservation->reservation_status === ReservationStatus::Confirmed) {
                    $stateMachine->transition($reservation, ReservationStatus::ContractPending, $inspector, "Auto-transition: Confirmed to Contract Pending during initial inspection.");
                }
                if ($reservation->reservation_status === ReservationStatus::InPreparation) {
                    $stateMachine->transition($reservation, ReservationStatus::ContractPending, $inspector, "Auto-transition: In Preparation to Contract Pending during initial inspection.");
                }
                if ($reservation->reservation_status === ReservationStatus::ContractPending) {
                    $stateMachine->transition($reservation, ReservationStatus::ContractSigned, $inspector, "Auto-transition: Contract Pending to Contract Signed during initial inspection.");
                }
                if ($reservation->reservation_status === ReservationStatus::ContractSigned) {
                    $stateMachine->transition($reservation, ReservationStatus::DeliveryAssigned, $inspector, "Auto-transition: Contract Signed to Delivery Assigned during initial inspection.");
                }
                if ($reservation->reservation_status === ReservationStatus::DeliveryAssigned) {
                    $stateMachine->transition($reservation, ReservationStatus::Delivered, $inspector, "Auto entregado para inspección inicial.");
                }
                if ($reservation->reservation_status === ReservationStatus::Delivered) {
                    $stateMachine->transition($reservation, ReservationStatus::Active, $inspector, "Inspección inicial aprobada. Renta activa.");
                }
            } elseif ($type === VehicleInspectionType::Final->value) {
                if ($reservation->reservation_status === ReservationStatus::Active) {
                    $stateMachine->transition($reservation, ReservationStatus::ReturnPending, $inspector, "Iniciando proceso de retorno.");
                }
                if ($reservation->reservation_status === ReservationStatus::ReturnPending) {
                    $stateMachine->transition($reservation, ReservationStatus::Returned, $inspector, "Auto devuelto para inspección final.");
                }
                if ($reservation->reservation_status === ReservationStatus::Returned) {
                    $stateMachine->transition($reservation, ReservationStatus::InspectionPending, $inspector, "Iniciando inspección final de retorno.");
                }
                if ($reservation->reservation_status === ReservationStatus::InspectionPending) {
                    $stateMachine->transition($reservation, ReservationStatus::Completed, $inspector, "Inspección final aprobada. Reserva completada.");
                }
            }

            return $inspection->refresh();
        });
    }

    /**
     * Sube una foto de evidencia para una inspección.
     */
    public function uploadPhoto(VehicleInspection $inspection, UploadedFile $file, string $position, ?string $note = null): InspectionPhoto
    {
        $path = $file->store("inspections/{$inspection->id}", 'local');

        return $inspection->photos()->create([
            'path' => $path,
            'position' => $position,
            'note' => $note,
        ]);
    }

    /**
     * Almacena de forma privada la firma del cliente.
     */
    private function storeSignature(VehicleInspection $inspection, mixed $signature): string
    {
        $path = "inspections/{$inspection->id}/signature_" . Str::random(8) . ".png";

        if (is_string($signature) && str_starts_with($signature, 'data:image')) {
            // Decodificar base64
            $data = explode(',', $signature);
            $decoded = base64_decode(end($data));
            Storage::disk('local')->put($path, $decoded);
        } elseif ($signature instanceof UploadedFile) {
            $path = $signature->storeAs("inspections/{$inspection->id}", "signature_" . Str::random(8) . ".png", 'local');
        } else {
            Storage::disk('local')->put($path, $signature);
        }

        return $path;
    }
}
