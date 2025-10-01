<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->resource['title'],
            'type' => $this->resource['type'],
            'data' => $this->resource['data'],
            'loading' => $this->resource['loading'] ?? false,
            'height' => $this->resource['height'] ?? null,
        ];
    }
}