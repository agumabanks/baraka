<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'current_status' => $this->current_status,
            'origin_branch' => $this->originBranch?->name,
            'dest_branch' => $this->destBranch?->name,
            'service_level' => $this->service_level,
            'incoterm' => $this->incoterm,
            'price_amount' => $this->price_amount,
            'currency' => $this->currency,
            'total_weight' => $this->total_weight,
            'total_parcels' => $this->total_parcels,
            'last_scan' => $this->last_scan,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
