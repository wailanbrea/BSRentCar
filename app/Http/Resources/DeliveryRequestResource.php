<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'delivery_zone_id' => $this->delivery_zone_id,
            'pickup_point_id' => $this->pickup_point_id,
            'delivery_time_window_id' => $this->delivery_time_window_id,
            'direction' => $this->direction,
            'type' => $this->type->value,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_km' => $this->distance_km,
            'fee' => $this->fee,
            'scheduled_date' => $this->scheduled_date->toDateString(),
            'status' => $this->status->value,
            'assigned_to' => $this->assigned_to,
            'notes' => $this->notes,
        ];
    }
}
