<?php

namespace App\Http\Resources\Sales;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reference' => sprintf('QT-%06d', $this->id),
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ] : null;
            }, null),
            'service_type' => $this->service_type,
            'destination_country' => $this->destination_country ? strtoupper($this->destination_country) : null,
            'pieces' => (int) $this->pieces,
            'weight_kg' => (float) $this->weight_kg,
            'volume_cm3' => $this->volume_cm3 ? (int) $this->volume_cm3 : null,
            'dim_factor' => $this->dim_factor ? (int) $this->dim_factor : null,
            'base_charge' => (float) $this->base_charge,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'valid_until' => $this->valid_until ? Carbon::parse($this->valid_until)->toDateString() : null,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toIso8601String() : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->toIso8601String() : null,
        ];
    }
}

