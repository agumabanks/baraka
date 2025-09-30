<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'origin_branch_id' => $this->origin_branch_id,
            'destination_country' => $this->destination_country,
            'service_type' => $this->service_type,
            'pieces' => $this->pieces,
            'weight_kg' => $this->weight_kg,
            'volume_cm3' => $this->volume_cm3,
            'dim_factor' => $this->dim_factor,
            'base_charge' => $this->base_charge,
            'surcharges' => $this->surcharges_json,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'valid_until' => $this->valid_until,
            'pdf_path' => $this->pdf_path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
