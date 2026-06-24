<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryTimeWindowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delivery_zone_id' => $this->delivery_zone_id,
            'label' => $this->label,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'days_of_week' => $this->days_of_week,
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
        ];
    }
}
