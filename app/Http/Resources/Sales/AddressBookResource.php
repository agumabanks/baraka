<?php

namespace App\Http\Resources\Sales;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressBookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'phone' => $this->phone_e164,
            'email' => $this->email,
            'country' => $this->country,
            'city' => $this->city,
            'address_line' => $this->address_line,
            'tax_id' => $this->tax_id,
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ] : null;
            }, null),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

