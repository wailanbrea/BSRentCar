<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_number' => $this->reservation_number,
            'vehicle_id' => $this->vehicle_id,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'pickup_type' => $this->pickup_type,
            'pickup_address' => $this->pickup_address,
            'return_type' => $this->return_type,
            'return_address' => $this->return_address,
            'totals' => [
                'base_price' => $this->base_price,
                'delivery_fee' => $this->delivery_fee,
                'insurance_fee' => $this->insurance_fee,
                'discount_amount' => $this->discount_amount,
                'tax_amount' => $this->tax_amount,
                'total_amount' => $this->total_amount,
                'deposit_amount' => $this->deposit_amount,
                'currency' => $this->currency,
            ],
            'payment_status' => $this->payment_status,
            'reservation_status' => $this->reservation_status,
            'contract_status' => $this->contract_status,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'created_at' => $this->created_at,
        ];
    }
}
