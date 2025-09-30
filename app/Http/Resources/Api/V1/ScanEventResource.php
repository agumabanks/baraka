<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScanEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sscc' => $this->sscc,
            'type' => $this->type,
            'branch' => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'occurred_at' => $this->occurred_at,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'geojson' => $this->geojson,
            ],
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}