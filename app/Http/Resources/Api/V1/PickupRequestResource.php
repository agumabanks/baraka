<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'pickup_date' => $this->pickup_date,
            'pickup_time' => $this->pickup_time,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'instructions' => $this->instructions,
            'status' => $this->status,
            'merchant' => [
                'id' => $this->merchant->id,
                'name' => $this->merchant->name,
                'company_name' => $this->merchant->company_name,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
