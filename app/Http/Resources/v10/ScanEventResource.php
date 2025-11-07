<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScanEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'status_after' => $this->status_after?->value,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'sscc' => $this->sscc,
            'shipment_id' => $this->shipment_id,
            'bag_id' => $this->bag_id,
            'route_id' => $this->route_id,
            'stop_id' => $this->stop_id,
            'branch_id' => $this->branch_id,
            'leg_id' => $this->leg_id,
            'user_id' => $this->user_id,
            'location_type' => $this->location_type,
            'location_id' => $this->location_id,
            'note' => $this->note,
            'geojson' => $this->geojson,
            'payload' => $this->payload,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
