<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_inspection_id' => $this->vehicle_inspection_id,
            'path' => $this->path,
            'position' => $this->position->value,
            'note' => $this->note,
        ];
    }
}
