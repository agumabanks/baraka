<?php

namespace App\Http\Controllers\Api\V10\Analytics;

use App\Http\Controllers\Controller;
use App\Services\OperationalReporting\OperationalMetricsService;
use App\Services\OperationalReporting\OriginDestinationAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OperationalAnalyticsController extends Controller
{
    public function __construct(
        private OperationalMetricsService $metricsService,
        private OriginDestinationAnalyticsService $originDestinationAnalyticsService
    ) {
    }

    public function metrics(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        [$dateRange, $filters] = $this->extractFilters($request);

        $metrics = $this->metricsService->getMetrics($dateRange, $filters);

        return $this->successResponse($metrics, $startedAt, [
            'date_range' => $dateRange,
            'totalRecords' => $metrics['originDestinationAnalytics']['totalVolume'] ?? 0,
        ]);
    }

    public function originDestination(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        [$dateRange, $filters] = $this->extractFilters($request);
        $granularity = $request->query('granularity', 'daily');

        $analytics = $this->originDestinationAnalyticsService->getVolumeAnalytics($dateRange, $granularity, $filters);

        return $this->successResponse($analytics, $startedAt, [
            'date_range' => $dateRange,
            'granularity' => $granularity,
            'totalRecords' => count($analytics),
        ]);
    }

    public function routeEfficiency(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        [$dateRange, $filters] = $this->extractFilters($request);
        $metrics = $this->metricsService->getMetrics($dateRange, $filters);

        return $this->successResponse($metrics['routeEfficiency'], $startedAt, [
            'date_range' => $dateRange,
            'totalRecords' => count($metrics['routeEfficiency']['routePerformance'] ?? []),
        ]);
    }

    public function onTimeDelivery(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        [$dateRange, $filters] = $this->extractFilters($request);
        $metrics = $this->metricsService->getMetrics($dateRange, $filters);

        return $this->successResponse($metrics['onTimeDelivery'], $startedAt, [
            'date_range' => $dateRange,
            'totalRecords' => count($metrics['onTimeDelivery']['trends'] ?? []),
        ]);
    }

    public function exceptionAnalysis(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        [$dateRange, $filters] = $this->extractFilters($request);
        $metrics = $this->metricsService->getMetrics($dateRange, $filters);

        return $this->successResponse($metrics['exceptionAnalysis'], $startedAt, [
            'date_range' => $dateRange,
            'totalRecords' => count($metrics['exceptionAnalysis']['exceptionTypes'] ?? []),
        ]);
    }

    public function driverPerformance(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        [$dateRange, $filters] = $this->extractFilters($request);
        $metrics = $this->metricsService->getMetrics($dateRange, $filters);

        return $this->successResponse($metrics['driverPerformance'], $startedAt, [
            'date_range' => $dateRange,
            'totalRecords' => count($metrics['driverPerformance']['rankings'] ?? []),
        ]);
    }

    public function containerUtilization(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        [$dateRange, $filters] = $this->extractFilters($request);
        $metrics = $this->metricsService->getMetrics($dateRange, $filters);

        return $this->successResponse($metrics['containerUtilization'], $startedAt, [
            'date_range' => $dateRange,
        ]);
    }

    public function transitTimeAnalysis(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        [$dateRange, $filters] = $this->extractFilters($request);
        $metrics = $this->metricsService->getMetrics($dateRange, $filters);

        return $this->successResponse($metrics['transitTimeAnalysis'], $startedAt, [
            'date_range' => $dateRange,
        ]);
    }

    /**
     * Extract date range and filter parameters from the inbound request.
     */
    private function extractFilters(Request $request): array
    {
        $start = $request->input('date_range.start', $request->query('start'));
        $end = $request->input('date_range.end', $request->query('end'));

        $dateRange = [
            'start' => $start ?? now()->subDays(30)->format('Ymd'),
            'end' => $end ?? now()->format('Ymd'),
        ];

        $filters = [];

        $clientId = $request->input('client_id', $request->query('client_id'));
        if ($clientId) {
            $filters['client_id'] = $clientId;
        }

        $originIds = Arr::wrap($request->input('origin_branch_id', $request->query('origin_branch_id')));
        if ($originIds && $originIds !== [null]) {
            $filters['origin_branch_ids'] = array_filter($originIds);
        }

        $destinationIds = Arr::wrap($request->input('destination_branch_id', $request->query('destination_branch_id')));
        if ($destinationIds && $destinationIds !== [null]) {
            $filters['destination_branch_ids'] = array_filter($destinationIds);
        }

        if ($minVolume = $request->input('min_volume', $request->query('min_volume'))) {
            $filters['min_volume'] = (int) $minVolume;
        }

        return [$dateRange, $filters];
    }

    private function successResponse(mixed $data, float $startedAt, array $metadata = []): JsonResponse
    {
        $defaultMetadata = [
            'timestamp' => now()->toISOString(),
            'source' => 'operational_analytics',
            'version' => '1.0',
            'totalRecords' => is_countable($data) ? count($data) : 1,
        ];

        $loadTimeMs = round((microtime(true) - $startedAt) * 1000, 2);

        return response()->json([
            'success' => true,
            'data' => $data,
            'metadata' => array_merge($defaultMetadata, $metadata),
            'performance' => [
                'loadTime' => $loadTimeMs,
                'cacheHit' => false,
            ],
        ]);
    }
}
