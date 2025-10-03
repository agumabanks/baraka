<?php

namespace App\Http\Resources\Sales;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $start = $this->start_date ? Carbon::parse($this->start_date) : null;
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ] : null;
            }, null),
            'status' => $this->status,
            'start_date' => $start?->toDateString(),
            'end_date' => $end?->toDateString(),
            'duration_days' => $start && $end ? $start->diffInDays($end) : null,
            'rate_card_id' => $this->rate_card_id,
            'notes' => $this->notes,
            'sla' => $this->sla_json ?? [],
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toIso8601String() : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->toIso8601String() : null,
        ];
    }
}

