<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'dateFilter' => $this->resource['dateFilter'],
            'healthKPIs' => [
                'slaStatus' => $this->resource['healthKPIs']['slaStatus'] ?? null,
                'exceptions' => $this->resource['healthKPIs']['exceptions'] ?? null,
                'onTimeDelivery' => $this->resource['healthKPIs']['onTimeDelivery'] ?? null,
                'openTickets' => $this->resource['healthKPIs']['openTickets'] ?? null,
            ],
            'coreKPIs' => KPICardResource::collection($this->resource['coreKPIs']),
            'workflowQueue' => WorkflowItemResource::collection($this->resource['workflowQueue']),
            'statements' => $this->resource['statements'],
            'charts' => $this->resource['charts'],
            'quickActions' => $this->resource['quickActions'],
            'loading' => false,
            'error' => null,
        ];
    }
}