<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPickupPointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delivery_zone_id' => $this->delivery_zone_id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'fee' => $this->fee,
            'is_active' => $this->is_active,
            'opening_hours' => $this->opening_hours,
            'notes' => $this->notes,
        ];
    }
}
