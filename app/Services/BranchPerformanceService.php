<?php

namespace App\Services;

use App\Enums\BranchStatus;
use App\Enums\ShipmentStatus;
use App\Models\Backend\Branch;
use App\Models\BranchAlert;
use App\Models\BranchMetric;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchPerformanceService
{
    public function __construct(
        protected BranchAnalyticsService $analytics,
        protected BranchCapacityService $capacity
    ) {}

    public function generateSnapshot(Branch $branch, string $window = 'daily', ?Carbon $forDate = null, bool $persist = true): BranchMetric
    {
        $forDate ??= now();
        $start = $this->windowStart($forDate, $window);
        $end = $this->windowEnd($forDate, $window);

        $shipments = $this->fetchShipments($branch, $start, $end);
        $throughput = $shipments->count();
        $delivered = $shipments->where('current_status', ShipmentStatus::DELIVERED->value)->count();
        $exceptions = $shipments->where('has_exception', true)->count();

        $capacity = $branch->capacity_parcels_per_day ?: max(1, $branch->activeWorkers()->count() * 10);
        $utilisation = $capacity > 0 ? min(1, $throughput / $capacity) : 0;
        $exceptionRate = $throughput > 0 ? $exceptions / $throughput : 0;
        $onTimeRate = $delivered > 0 ? $shipments
            ->filter(fn ($shipment) => $shipment->delivered_at && $shipment->delivered_at <= $shipment->expected_delivery_date)
            ->count() / $delivered : 0;

        $metric = $branch->metrics()->firstOrNew([
            'snapshot_date' => $start->toDateString(),
            'window' => $window,
        ]);

        $metric->fill([
            'throughput_count' => $throughput,
            'capacity_utilization' => round($utilisation, 3),
            'exception_rate' => round($exceptionRate, 3),
            'on_time_rate' => round($onTimeRate, 3),
            'average_processing_time_hours' => $this->processingTime($shipments),
            'alerts_triggered' => $branch->alerts()->whereBetween('triggered_at', [$start, $end])->count(),
            'calculated_at' => now(),
            'metadata' => [
                'window_start' => $start->toDateTimeString(),
                'window_end' => $end->toDateTimeString(),
                'capacity_reference' => $capacity,
            ],
        ]);

        if ($persist) {
            $metric->save();
            $this->evaluateAlertRules($branch, $metric);
        }

        return $metric;
    }

    protected function fetchShipments(Branch $branch, Carbon $start, Carbon $end): Collection
    {
        return $branch->originShipments()
            ->whereBetween('created_at', [$start, $end])
            ->get();
    }

    protected function processingTime(Collection $shipments): ?float
    {
        $delivered = $shipments->filter(function ($shipment) {
            return $shipment->delivered_at && $shipment->created_at;
        });

        if ($delivered->isEmpty()) {
            return null;
        }

        $hours = $delivered->sum(function ($shipment) {
            return $shipment->created_at->diffInHours($shipment->delivered_at);
        });

        return round($hours / $delivered->count(), 2);
    }

    protected function evaluateAlertRules(Branch $branch, BranchMetric $metric): void
    {
        $thresholds = [
            'exception_rate' => config('operations.branch_alerts.exception_rate', 0.2),
            'under_utilization' => config('operations.branch_alerts.under_utilization', 0.4),
            'over_utilization' => config('operations.branch_alerts.over_utilization', 1.1),
        ];

        if ($metric->exception_rate >= $thresholds['exception_rate']) {
            $this->createAlert($branch, 'EXCEPTION_RATE', 'high', 'High exception rate detected', [
                'exception_rate' => $metric->exception_rate,
                'snapshot_date' => $metric->snapshot_date,
            ]);
        }

        if ($metric->capacity_utilization <= $thresholds['under_utilization']) {
            $this->createAlert($branch, 'UNDER_UTILIZATION', 'medium', 'Branch under-utilized', [
                'capacity_utilization' => $metric->capacity_utilization,
            ]);
        }

        if ($metric->capacity_utilization >= $thresholds['over_utilization']) {
            $this->createAlert($branch, 'OVER_CAPACITY', 'high', 'Branch operating above capacity', [
                'capacity_utilization' => $metric->capacity_utilization,
            ]);
        }
    }

    protected function createAlert(Branch $branch, string $type, string $severity, string $message, array $context = []): void
    {
        BranchAlert::create([
            'branch_id' => $branch->id,
            'alert_type' => $type,
            'severity' => strtoupper($severity),
            'status' => 'OPEN',
            'title' => $message,
            'message' => $message,
            'context' => $context,
            'triggered_at' => now(),
        ]);
    }

    protected function windowStart(Carbon $date, string $window): Carbon
    {
        return match ($window) {
            'weekly' => $date->copy()->startOfWeek(),
            'monthly' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };
    }

    protected function windowEnd(Carbon $date, string $window): Carbon
    {
        return match ($window) {
            'weekly' => $date->copy()->endOfWeek(),
            'monthly' => $date->copy()->endOfMonth(),
            default => $date->copy()->endOfDay(),
        };
    }
}
