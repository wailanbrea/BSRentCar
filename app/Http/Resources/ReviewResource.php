<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'customer_id' => $this->customer_id,
            'vehicle_id' => $this->vehicle_id,
            'rating_vehicle' => $this->rating_vehicle,
            'rating_cleanliness' => $this->rating_cleanliness,
            'rating_service' => $this->rating_service,
            'rating_delivery' => $this->rating_delivery,
            'rating_overall' => $this->rating_overall,
            'comment' => $this->comment,
            'status' => $this->status->value ?? $this->status,
            'customer' => $this->relationLoaded('customer') ? [
                'id' => $this->customer->id,
                'first_name' => $this->customer->first_name,
                'last_name' => $this->customer->last_name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
