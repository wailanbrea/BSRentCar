<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'birthdate' => $this->birthdate?->toDateString(),
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'license_number' => $this->license_number,
            'verification_status' => $this->verification_status,
            'has_approved_license' => $this->hasApprovedLicense(),
            'documents' => CustomerDocumentResource::collection($this->whenLoaded('documents')),
        ];
    }
}
