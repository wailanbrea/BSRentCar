<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleInspectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'vehicle_id' => $this->vehicle_id,
            'type' => $this->type->value,
            'fuel_level' => $this->fuel_level,
            'mileage' => $this->mileage,
            'damages' => $this->damages,
            'notes' => $this->notes,
            'signature_path' => $this->signature_path,
            'accepted_by_customer' => $this->accepted_by_customer,
            'inspector_id' => $this->inspector_id,
            'inspected_at' => $this->inspected_at->toIso8601String(),
            'photos' => InspectionPhotoResource::collection($this->whenLoaded('photos')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
