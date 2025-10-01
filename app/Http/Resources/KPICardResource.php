<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KPICardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'value' => $this->resource['value'],
            'subtitle' => $this->resource['subtitle'] ?? null,
            'icon' => $this->resource['icon'] ?? null,
            'trend' => $this->resource['trend'] ?? null,
            'state' => $this->resource['state'] ?? 'neutral',
            'drilldownRoute' => $this->resource['drilldownRoute'] ?? null,
            'tooltip' => $this->resource['tooltip'] ?? null,
            'loading' => $this->resource['loading'] ?? false,
        ];
    }
}