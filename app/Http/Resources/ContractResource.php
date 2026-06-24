<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'number' => $this->number,
            'status' => $this->status->value,
            'signed_by_customer_at' => $this->signed_by_customer_at?->toIso8601String(),
            'signature_meta' => $this->signature_meta,
            'generated_by' => $this->generated_by,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
