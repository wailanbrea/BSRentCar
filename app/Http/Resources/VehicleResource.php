<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'category' => $this->category,
            'transmission' => $this->transmission,
            'seats' => $this->seats,
            'doors' => $this->doors,
            'fuel_type' => $this->fuel_type,
            'daily_price' => $this->daily_price,
            'deposit_amount' => $this->deposit_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'location_id' => $this->location_id,
            'primary_image' => $this->whenLoaded('primaryImage', fn () => $this->primaryImage
                ? Storage::disk('public')->url($this->primaryImage->path)
                : null),
            // Sólo en detalle (cuando se cargan las relaciones):
            'description' => $this->when($this->resource->relationLoaded('features'), $this->description),
            'rules' => $this->when($this->resource->relationLoaded('features'), $this->rules),
            'images' => VehicleImageResource::collection($this->whenLoaded('images')),
            'features' => $this->whenLoaded('features', fn () => $this->features->map(fn ($f) => [
                'name' => $f->name,
                'icon' => $f->icon,
            ])),
        ];
    }
}
