<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'driver_id' => $this->driver_id,
            'signature_url' => $this->signature_url,
            'photo_url' => $this->photo_url,
            'otp_code' => $this->otp_code, // Only show if not verified
            'verified_at' => $this->verified_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}