<?php

namespace App\Services\OperationalReporting;

use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OperationalMetricsService
{
    public function __construct(
        private OriginDestinationAnalyticsService $originDestinationAnalyticsService
    ) {
    }

    public function getMetrics(array $dateRange = [], array $filters = []): array
    {
        [$startDate, $endDate] = $this->normalizeDateRange($dateRange);

        $baseQuery = Shipment::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (isset($filters['client_id'])) {
            $baseQuery->where('client_id', $filters['client_id']);
        }

        $totalShipments = (clone $baseQuery)->count();
        $deliveredShipments = (clone $baseQuery)->whereNotNull('delivered_at')->count();
        $onTimeDeliveries = (clone $baseQuery)
            ->whereNotNull('delivered_at')
            ->whereNotNull('expected_delivery_date')
            ->whereColumn('delivered_at', '<=', 'expected_delivery_date')
            ->count();

        $originDestinationData = $this->originDestinationAnalyticsService->getVolumeAnalytics(
            ['start' => $startDate->format('Ymd'), 'end' => $endDate->format('Ymd')],
            'daily',
            $filters
        );

        $topRoutesRaw = collect($originDestinationData)
            ->take(5)
            ->map(function (array $route) {
                $originCoords = $route['origin_branch']['coordinates'] ?? null;
                $destinationCoords = $route['destination_branch']['coordinates'] ?? null;

                $coordinatePairs = [];
                if (is_array($originCoords) && isset($originCoords['lat'], $originCoords['lng'])) {
                    $coordinatePairs[] = [(float) $originCoords['lat'], (float) $originCoords['lng']];
                }
                if (is_array($destinationCoords) && isset($destinationCoords['lat'], $destinationCoords['lng'])) {
                    $coordinatePairs[] = [(float) $destinationCoords['lat'], (float) $destinationCoords['lng']];
                }

                $routeIdentifier = $this->buildRouteIdentifier(
                    $route['origin_branch']['id'] ?? null,
                    $route['destination_branch']['id'] ?? null
                );

                return [
                    'identifier' => $routeIdentifier,
                    'data' => [
                        'route' => ($route['origin_branch']['name'] ?? 'Unknown') . ' → ' . ($route['destination_branch']['name'] ?? 'Unknown'),
                        'origin' => $route['origin_branch']['name'] ?? 'Unknown',
                        'destination' => $route['destination_branch']['name'] ?? 'Unknown',
                        'volume' => $route['metrics']['total_shipments'],
                        'revenue' => $route['metrics']['total_revenue'],
                        'efficiency' => $route['metrics']['on_time_rate'],
                        'coordinates' => $coordinatePairs,
                    ],
                ];
            })
            ->values()
            ->toArray();

        $topRoutes = array_map(static fn (array $item) => $item['data'], $topRoutesRaw);

        $geographicDistribution = collect($originDestinationData)
            ->groupBy(fn ($route) => $route['origin_branch']['name'] ?? 'Unknown')
            ->map(function ($group, $region) {
                $shipmentCount = $group->sum(fn ($route) => $route['metrics']['total_shipments']);
                $revenue = $group->sum(fn ($route) => $route['metrics']['total_revenue']);
                $firstCoordinates = $group->first()['origin_branch']['coordinates'] ?? null;

                return [
                    'region' => $region,
                    'coordinates' => ($firstCoordinates && isset($firstCoordinates['lat'], $firstCoordinates['lng']))
                        ? [(float) $firstCoordinates['lat'], (float) $firstCoordinates['lng']]
                        : [0, 0],
                    'value' => $shipmentCount,
                    'shipmentCount' => $shipmentCount,
                    'revenue' => $revenue,
                ];
            })
            ->values()
            ->toArray();

        $onTimeRate = $deliveredShipments > 0
            ? round(($onTimeDeliveries / $deliveredShipments) * 100, 2)
            : 0.0;

        $onTimeTrends = (clone $baseQuery)
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN delivered_at IS NOT NULL AND expected_delivery_date IS NOT NULL AND delivered_at <= expected_delivery_date THEN 1 ELSE 0 END) as on_time'),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->limit(14)
            ->get()
            ->map(function ($row) {
                $rate = $row->total > 0 ? round(($row->on_time / $row->total) * 100, 2) : 0.0;

                return [
                    'label' => $row->date,
                    'value' => $rate,
                ];
            })
            ->toArray();

        $exceptionCounts = (clone $baseQuery)
            ->where('has_exception', true)
            ->select([
                'exception_type',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('exception_type')
            ->orderByDesc('count')
            ->get();

        $exceptionSummary = $exceptionCounts->map(function ($row) use ($deliveredShipments) {
            $percentage = $deliveredShipments > 0 ? round(($row->count / $deliveredShipments) * 100, 2) : 0.0;

            return [
                'type' => $row->exception_type ?? 'Unknown',
                'count' => (int) $row->count,
                'percentage' => $percentage,
                'impact' => $percentage > 5 ? 'high' : ($percentage > 2 ? 'medium' : 'low'),
                'trend' => 'stable',
            ];
        })->toArray();

        $averageTransitHours = (clone $baseQuery)
            ->whereNotNull('delivered_at')
            ->whereNotNull('picked_up_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'originDestinationAnalytics' => [
                'totalVolume' => $totalShipments,
                'growthRate' => 0.0,
                'topRoutes' => $topRoutes,
                'geographicDistribution' => $geographicDistribution,
            ],
            'routeEfficiency' => [
                'overallScore' => $onTimeRate,
                'routePerformance' => collect($topRoutesRaw)->map(function (array $route) use ($onTimeRate) {
                    return [
                        'routeId' => $route['identifier'],
                        'routeName' => $route['data']['route'],
                        'efficiency' => $route['data']['efficiency'],
                        'onTimeRate' => $route['data']['efficiency'],
                        'cost' => 0,
                        'utilization' => $onTimeRate,
                    ];
                })->toArray(),
                'optimizationOpportunities' => [],
            ],
            'onTimeDelivery' => [
                'rate' => $onTimeRate,
                'variance' => 0,
                'trends' => $onTimeTrends,
                'performanceByRegion' => [],
            ],
            'exceptionAnalysis' => [
                'totalExceptions' => array_sum(array_column($exceptionSummary, 'count')),
                'exceptionTypes' => $exceptionSummary,
                'rootCauses' => [],
            ],
            'driverPerformance' => [
                'rankings' => [],
                'utilization' => [],
                'performanceMetrics' => [],
            ],
            'containerUtilization' => [
                'utilizationRate' => 0,
                'efficiencyScore' => 0,
                'optimization' => [],
            ],
            'transitTimeAnalysis' => [
                'averageTime' => $averageTransitHours ? round((float) $averageTransitHours, 2) : 0,
                'bottleneckAnalysis' => [],
                'improvementOpportunities' => [],
            ],
        ];
    }

    private function normalizeDateRange(array $dateRange): array
    {
        $start = $dateRange['start'] ?? now()->subDays(30)->format('Ymd');
        $end = $dateRange['end'] ?? now()->format('Ymd');

        return [
            Carbon::createFromFormat('Ymd', $start)->startOfDay(),
            Carbon::createFromFormat('Ymd', $end)->endOfDay(),
        ];
    }

    private function buildRouteIdentifier(?int $originId, ?int $destinationId): string
    {
        return sprintf('%s:%s', $originId ?? 0, $destinationId ?? 0);
    }
}
