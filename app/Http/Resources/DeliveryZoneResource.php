<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'polygon' => $this->polygon,
            'color' => $this->color,
            'origin_latitude' => $this->origin_latitude,
            'origin_longitude' => $this->origin_longitude,
            'allows_home_delivery' => $this->allows_home_delivery,
            'base_fee' => $this->base_fee,
            'free_radius_km' => $this->free_radius_km,
            'price_per_km' => $this->price_per_km,
            'max_distance_km' => $this->max_distance_km,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
        ];
    }
}
