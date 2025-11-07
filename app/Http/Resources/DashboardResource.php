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
        $coreKPIs = array_map(fn ($kpi) => (new KPICardResource($kpi))->toArray($request), $this->resource['coreKPIs'] ?? []);
        $workflowQueue = array_map(fn ($item) => (new WorkflowItemResource($item))->toArray($request), $this->resource['workflowQueue'] ?? []);

        $charts = [];
        if (isset($this->resource['charts']) && is_array($this->resource['charts'])) {
            foreach ($this->resource['charts'] as $key => $chart) {
                if ($chart === null) {
                    $charts[$key] = null;
                    continue;
                }
                $charts[$key] = (new ChartDataResource($chart))->toArray($request);
            }
        }

        return [
            'dateFilter' => $this->resource['dateFilter'],
            'healthKPIs' => [
                'slaStatus' => $this->resource['healthKPIs']['slaStatus'] ?? null,
                'exceptions' => $this->resource['healthKPIs']['exceptions'] ?? null,
                'onTimeDelivery' => $this->resource['healthKPIs']['onTimeDelivery'] ?? null,
                'openTickets' => $this->resource['healthKPIs']['openTickets'] ?? null,
            ],
            'coreKPIs' => $coreKPIs,
            'workflowQueue' => $workflowQueue,
            'statements' => $this->resource['statements'],
            'charts' => $charts,
            'quickActions' => $this->resource['quickActions'],
            'teamOverview' => $this->resource['teamOverview'] ?? [],
            'activityTimeline' => $this->resource['activityTimeline'] ?? [],
            'loading' => false,
            'error' => null,
        ];
    }
}
