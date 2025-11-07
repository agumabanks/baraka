<?php

namespace App\Services\OperationalReporting;

use App\Models\Backend\Branch;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OriginDestinationAnalyticsService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get volume analytics by origin-destination pairs
     */
    public function getVolumeAnalytics(array $dateRange, string $granularity = 'daily', array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('volume_analytics', array_merge($dateRange, $filters, ['granularity' => $granularity]));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateRange, $filters) {
            [$startDate, $endDate] = $this->normalizeDateRange($dateRange);

            $query = Shipment::query()
                ->select(
                    'origin_branch_id',
                    'dest_branch_id',
                    DB::raw('COUNT(*) as total_shipments'),
                    DB::raw('COALESCE(SUM(price_amount), 0) as total_revenue'),
                    DB::raw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_delivery_hours'),
                    DB::raw('SUM(CASE WHEN delivered_at IS NOT NULL AND expected_delivery_date IS NOT NULL AND delivered_at <= expected_delivery_date THEN 1 ELSE 0 END) as on_time_deliveries')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('origin_branch_id')
                ->whereNotNull('dest_branch_id')
                ->groupBy('origin_branch_id', 'dest_branch_id');

            $this->applyFilters($query, $filters);

            $results = $query->get();

            if ($results->isEmpty()) {
                return [];
            }

            $branchIds = $results->pluck('origin_branch_id')
                ->merge($results->pluck('dest_branch_id'))
                ->filter()
                ->unique()
                ->all();

            $branches = Branch::whereIn('id', $branchIds)->get()->keyBy('id');

            return $results->map(function ($row) use ($branches) {
                $origin = $branches->get($row->origin_branch_id);
                $destination = $branches->get($row->dest_branch_id);

                $onTimeRate = $row->total_shipments > 0
                    ? round(($row->on_time_deliveries / $row->total_shipments) * 100, 2)
                    : 0.0;

                return [
                    'origin_branch' => [
                        'id' => $row->origin_branch_id,
                        'name' => $origin?->name ?? 'Unknown',
                        'coordinates' => $this->extractCoordinates($origin)
                    ],
                    'destination_branch' => [
                        'id' => $row->dest_branch_id,
                        'name' => $destination?->name ?? 'Unknown',
                        'coordinates' => $this->extractCoordinates($destination)
                    ],
                    'metrics' => [
                        'total_shipments' => (int) $row->total_shipments,
                        'total_revenue' => (float) $row->total_revenue,
                        'avg_delivery_hours' => $row->avg_delivery_hours ? round($row->avg_delivery_hours, 2) : null,
                        'on_time_rate' => $onTimeRate,
                        'avg_revenue_per_shipment' => $row->total_shipments > 0
                            ? round($row->total_revenue / $row->total_shipments, 2)
                            : 0.0,
                    ]
                ];
            })->sortByDesc(fn ($route) => $route['metrics']['total_shipments'])->values()->toArray();
        });
    }

    /**
     * Generate geographic heat map data
     */
    public function generateHeatMapData(array $dateRange, array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('heatmap_data', array_merge($dateRange, $filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateRange, $filters) {
            [$startDate, $endDate] = $this->normalizeDateRange($dateRange);

            $query = Shipment::query()
                ->select(
                    'origin_branch_id',
                    DB::raw('COUNT(*) as shipment_density')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('origin_branch_id')
                ->groupBy('origin_branch_id');

            $this->applyFilters($query, $filters);

            $results = $query->get();

            if ($results->isEmpty()) {
                return [
                    'heatmap_points' => [],
                    'total_routes' => 0,
                    'peak_density' => 0,
                ];
            }

            $branches = Branch::whereIn('id', $results->pluck('origin_branch_id')->all())
                ->get()
                ->keyBy('id');

            $points = $results->map(function ($row) use ($branches) {
                $branch = $branches->get($row->origin_branch_id);

                if (!$branch) {
                    return null;
                }

                $lat = $branch->latitude ?? $branch->geo_lat;
                $lng = $branch->longitude ?? $branch->geo_lng;

                if ($lat === null || $lng === null) {
                    return null;
                }

                return [
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'intensity' => (float) min($row->shipment_density / 100, 1),
                ];
            })->filter()->values();

            return [
                'heatmap_points' => $points->toArray(),
                'total_routes' => $points->count(),
                'peak_density' => $results->max('shipment_density') ?? 0,
            ];
        });
    }

    /**
     * Get route volume trends and patterns
     */
    public function getRouteVolumeTrends(string $routeId, array $dateRange): array
    {
        $cacheKey = "route_volume_trends_{$routeId}_{$dateRange['start']}_{$dateRange['end']}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($routeId, $dateRange) {
            [$originId, $destinationId] = $this->parseRouteIdentifier($routeId);

            $trends = Shipment::query()
                ->select(
                    DB::raw('DATE(delivered_at) as date'),
                    DB::raw('COUNT(*) as daily_shipments'),
                    DB::raw('COALESCE(SUM(price_amount), 0) as total_revenue')
                )
                ->whereNotNull('delivered_at')
                ->when($originId, fn ($query) => $query->where('origin_branch_id', $originId))
                ->when($destinationId, fn ($query) => $query->where('dest_branch_id', $destinationId))
                ->whereBetween('delivered_at', $this->normalizeDateRange($dateRange))
                ->groupBy(DB::raw('DATE(delivered_at)'))
                ->orderBy(DB::raw('DATE(delivered_at)'))
                ->get();

            return [
                'route_id' => $routeId,
                'date_range' => $dateRange,
                'daily_trends' => $trends->map(function ($row) {
                    return [
                        'date' => $row->date,
                        'shipments' => (int) $row->daily_shipments,
                        'revenue' => (float) $row->total_revenue,
                    ];
                })->toArray(),
                'summary' => [
                    'total_days' => count($trends),
                    'avg_daily_shipments' => round($trends->avg('daily_shipments'), 2),
                    'total_volume' => $trends->sum('daily_shipments'),
                    'trend_direction' => $this->calculateTrendDirection($trends),
                ],
            ];
        });
    }

    /**
     * Export geographic data in various formats
     */
    public function exportGeographicData(string $format, array $dateRange, array $filters = []): string
    {
        $data = $this->getVolumeAnalytics($dateRange, 'daily', $filters);

        switch ($format) {
            case 'csv':
                return $this->exportToCSV($data, 'geographic_volume_analytics');
            case 'excel':
                return $this->exportToExcel($data, 'geographic_volume_analytics');
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Identify high-volume origin-destination pairs
     */
    public function getHighVolumePairs(int $limit = 10, array $dateRange = []): array
    {
        $cacheKey = "high_volume_pairs_{$limit}_" . md5(json_encode($dateRange));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit, $dateRange) {
            [$startDate, $endDate] = $this->normalizeDateRange($dateRange);

            return Shipment::query()
                ->select(
                    'origin_branch_id',
                    'dest_branch_id',
                    DB::raw('COUNT(*) as volume'),
                    DB::raw('COALESCE(SUM(price_amount), 0) as total_revenue')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('origin_branch_id')
                ->whereNotNull('dest_branch_id')
                ->groupBy('origin_branch_id', 'dest_branch_id')
                ->orderByDesc('volume')
                ->limit($limit)
                ->get()
                ->map(function ($row) {
                    $origin = Branch::find($row->origin_branch_id);
                    $destination = Branch::find($row->dest_branch_id);

                    return [
                        'route' => trim(($origin?->name ?? 'Unknown') . ' → ' . ($destination?->name ?? 'Unknown')),
                        'route_identifier' => $this->buildRouteIdentifier($row->origin_branch_id, $row->dest_branch_id),
                        'volume' => (int) $row->volume,
                        'revenue' => (float) $row->total_revenue,
                        'branch_ids' => [
                            'origin' => $row->origin_branch_id,
                            'destination' => $row->dest_branch_id,
                        ],
                    ];
                })->toArray();
        });
    }

    // Private helper methods
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['date_range'])) {
            [$startDate, $endDate] = $this->normalizeDateRange($filters['date_range']);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if (isset($filters['origin_branch_ids']) && is_array($filters['origin_branch_ids'])) {
            $query->whereIn('origin_branch_id', $filters['origin_branch_ids']);
        }

        if (isset($filters['destination_branch_ids']) && is_array($filters['destination_branch_ids'])) {
            $query->whereIn('dest_branch_id', $filters['destination_branch_ids']);
        }

        if (isset($filters['min_volume'])) {
            $query->having('total_shipments', '>=', (int) $filters['min_volume']);
        }
    }

    private function calculateTrendDirection(Collection $trends): string
    {
        if ($trends->count() < 2) {
            return 'insufficient_data';
        }

        $half = (int) floor($trends->count() / 2);
        $firstHalf = $trends->take($half)->avg('daily_shipments') ?: 0;
        $secondHalf = $trends->skip($half)->avg('daily_shipments') ?: 0;

        if ($firstHalf === 0) {
            return $secondHalf > 0 ? 'increasing' : 'stable';
        }

        $changePercent = (($secondHalf - $firstHalf) / $firstHalf) * 100;

        if ($changePercent > 5) {
            return 'increasing';
        }

        if ($changePercent < -5) {
            return 'decreasing';
        }

        return 'stable';
    }

    private function generateCacheKey(string $type, array $params): string
    {
        ksort($params);
        return "operational_reporting_{$type}_" . md5(serialize($params));
    }

    private function exportToCSV(array $data, string $filename): string
    {
        $filename .= '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        return $filename;
    }

    private function exportToExcel(array $data, string $filename): string
    {
        $filename .= '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return $filename;
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

    private function extractCoordinates(?Branch $branch): ?array
    {
        if (!$branch) {
            return null;
        }

        $lat = $branch->latitude ?? $branch->geo_lat;
        $lng = $branch->longitude ?? $branch->geo_lng;

        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }

    private function buildRouteIdentifier(?int $originId, ?int $destinationId): string
    {
        return sprintf('%s:%s', $originId ?? 0, $destinationId ?? 0);
    }

    private function parseRouteIdentifier(string $identifier): array
    {
        if (str_contains($identifier, ':')) {
            [$origin, $destination] = array_pad(explode(':', $identifier), 2, null);
            return [
                $origin !== null ? (int) $origin : null,
                $destination !== null ? (int) $destination : null,
            ];
        }

        return [null, null];
    }
}