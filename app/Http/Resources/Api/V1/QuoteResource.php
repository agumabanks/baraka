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
            'origin' => $this->origin,
            'destination' => $this->destination,
            'weight' => $this->weight,
            'service_type' => $this->service_type,
            'customer_id' => $this->customer_id,
            'created_at' => $this->created_at,
        ];
    }
}
