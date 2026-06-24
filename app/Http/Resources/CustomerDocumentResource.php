<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size,
            'reviewed_at' => $this->reviewed_at,
            'expires_at' => $this->expires_at?->toDateString(),
            'created_at' => $this->created_at,
            // file_path NO se expone; el acceso es vía URL firmada temporal (futuro endpoint).
        ];
    }
}
