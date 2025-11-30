<?php

namespace App\Services\Analytics;

use App\Models\Shipment;
use App\Models\ScanEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PredictiveAnalyticsService
 * 
 * Machine learning-inspired delivery time predictions:
 * - Historical pattern analysis
 * - Route-based ETA calculation
 * - Factor-weighted predictions
 */
class PredictiveAnalyticsService
{
    /**
     * Predict delivery time for a shipment
     */
    public function predictDeliveryTime(Shipment $shipment): array
    {
        $factors = [];
        $baseHours = $this->getBaseDeliveryHours($shipment);
        $factors['base_hours'] = $baseHours;

        // Factor 1: Route historical performance
        $routeFactor = $this->getRoutePerformanceFactor($shipment);
        $factors['route_factor'] = $routeFactor;

        // Factor 2: Day of week impact
        $dayFactor = $this->getDayOfWeekFactor();
        $factors['day_factor'] = $dayFactor;

        // Factor 3: Current workload
        $workloadFactor = $this->getWorkloadFactor($shipment);
        $factors['workload_factor'] = $workloadFactor;

        // Factor 4: Weather/season (simplified)
        $seasonFactor = $this->getSeasonalFactor();
        $factors['season_factor'] = $seasonFactor;

        // Factor 5: Shipment type
        $typeFactor = $this->getShipmentTypeFactor($shipment);
        $factors['type_factor'] = $typeFactor;

        // Calculate adjusted hours
        $adjustedHours = $baseHours 
            * $routeFactor 
            * $dayFactor 
            * $workloadFactor 
            * $seasonFactor 
            * $typeFactor;

        // Calculate confidence score
        $confidence = $this->calculateConfidence($shipment, $factors);

        // Predicted delivery time
        $startTime = $shipment->picked_up_at ?? now();
        $predictedDelivery = Carbon::parse($startTime)->addHours($adjustedHours);

        // Store prediction
        $this->storePrediction($shipment, $predictedDelivery, $confidence, $factors);

        return [
            'predicted_delivery_at' => $predictedDelivery->toIso8601String(),
            'predicted_hours' => round($adjustedHours, 1),
            'confidence_score' => $confidence,
            'factors' => $factors,
            'range' => [
                'earliest' => $predictedDelivery->copy()->subHours($adjustedHours * 0.2)->toIso8601String(),
                'latest' => $predictedDelivery->copy()->addHours($adjustedHours * 0.3)->toIso8601String(),
            ],
        ];
    }

    /**
     * Get base delivery hours for route
     */
    protected function getBaseDeliveryHours(Shipment $shipment): float
    {
        // Get historical average for this route
        $avgHours = Shipment::where('status', 'delivered')
            ->where('origin_branch_id', $shipment->origin_branch_id)
            ->where('dest_branch_id', $shipment->dest_branch_id)
            ->whereNotNull('picked_up_at')
            ->whereNotNull('delivered_at')
            ->where('created_at', '>=', now()->subMonths(3))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_hours')
            ->value('avg_hours');

        // If no historical data, use distance-based estimate
        if (!$avgHours) {
            // Default based on whether same city or inter-city
            $avgHours = $shipment->origin_branch_id === $shipment->dest_branch_id 
                ? 24 // Same branch/city
                : 48; // Different city
        }

        return max(2, min(168, $avgHours)); // Between 2 hours and 7 days
    }

    /**
     * Get route performance factor
     */
    protected function getRoutePerformanceFactor(Shipment $shipment): float
    {
        // Recent performance vs historical
        $recentAvg = Shipment::where('status', 'delivered')
            ->where('origin_branch_id', $shipment->origin_branch_id)
            ->where('dest_branch_id', $shipment->dest_branch_id)
            ->where('created_at', '>=', now()->subWeeks(2))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_hours')
            ->value('avg_hours');

        $historicalAvg = Shipment::where('status', 'delivered')
            ->where('origin_branch_id', $shipment->origin_branch_id)
            ->where('dest_branch_id', $shipment->dest_branch_id)
            ->where('created_at', '>=', now()->subMonths(3))
            ->where('created_at', '<', now()->subWeeks(2))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_hours')
            ->value('avg_hours');

        if (!$recentAvg || !$historicalAvg) {
            return 1.0;
        }

        // If recent is slower, increase factor
        return min(1.5, max(0.7, $recentAvg / $historicalAvg));
    }

    /**
     * Get day of week impact factor
     */
    protected function getDayOfWeekFactor(): float
    {
        $dayOfWeek = now()->dayOfWeek;

        // Historical delivery times by day
        return match ($dayOfWeek) {
            0 => 1.3, // Sunday - slower
            5 => 1.1, // Friday - slightly slower
            6 => 1.2, // Saturday - slower
            default => 1.0, // Weekdays normal
        };
    }

    /**
     * Get current workload factor
     */
    protected function getWorkloadFactor(Shipment $shipment): float
    {
        // Count pending shipments for destination branch
        $pendingCount = Shipment::whereIn('status', ['in_transit', 'out_for_delivery'])
            ->where('dest_branch_id', $shipment->dest_branch_id)
            ->count();

        // Get average capacity
        $avgPending = Shipment::whereIn('status', ['in_transit', 'out_for_delivery'])
            ->where('dest_branch_id', $shipment->dest_branch_id)
            ->where('created_at', '>=', now()->subMonth())
            ->selectRaw('AVG(1) as avg')
            ->value('avg') ?? 50;

        $ratio = $avgPending > 0 ? $pendingCount / $avgPending : 1;

        // Cap the factor
        return min(1.5, max(0.8, $ratio));
    }

    /**
     * Get seasonal factor
     */
    protected function getSeasonalFactor(): float
    {
        $month = now()->month;

        // Peak seasons (holidays, etc.)
        return match (true) {
            in_array($month, [11, 12]) => 1.2, // Holiday season
            $month === 1 => 1.1, // Post-holiday
            in_array($month, [6, 7, 8]) => 0.95, // Summer (typically slower)
            default => 1.0,
        };
    }

    /**
     * Get shipment type factor
     */
    protected function getShipmentTypeFactor(Shipment $shipment): float
    {
        $type = $shipment->shipment_type ?? 'standard';

        return match ($type) {
            'express', 'same_day' => 0.5,
            'next_day' => 0.7,
            'economy' => 1.3,
            default => 1.0,
        };
    }

    /**
     * Calculate prediction confidence score
     */
    protected function calculateConfidence(Shipment $shipment, array $factors): float
    {
        $confidence = 100;

        // Reduce confidence based on data availability
        $historicalCount = Shipment::where('status', 'delivered')
            ->where('origin_branch_id', $shipment->origin_branch_id)
            ->where('dest_branch_id', $shipment->dest_branch_id)
            ->count();

        if ($historicalCount < 10) {
            $confidence -= 30;
        } elseif ($historicalCount < 50) {
            $confidence -= 15;
        } elseif ($historicalCount < 100) {
            $confidence -= 5;
        }

        // Reduce confidence for high variability routes
        $stdDev = Shipment::where('status', 'delivered')
            ->where('origin_branch_id', $shipment->origin_branch_id)
            ->where('dest_branch_id', $shipment->dest_branch_id)
            ->whereNotNull('picked_up_at')
            ->whereNotNull('delivered_at')
            ->selectRaw('STDDEV(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as std_dev')
            ->value('std_dev');

        if ($stdDev > 24) {
            $confidence -= 20;
        } elseif ($stdDev > 12) {
            $confidence -= 10;
        }

        // Reduce for extreme factors
        foreach ($factors as $factor) {
            if (is_numeric($factor) && ($factor > 1.3 || $factor < 0.7)) {
                $confidence -= 5;
            }
        }

        return max(20, min(95, $confidence));
    }

    /**
     * Store prediction in database
     */
    protected function storePrediction(
        Shipment $shipment, 
        Carbon $predictedDelivery, 
        float $confidence, 
        array $factors
    ): void {
        DB::table('delivery_predictions')->updateOrInsert(
            ['shipment_id' => $shipment->id],
            [
                'predicted_delivery_at' => $predictedDelivery,
                'confidence_score' => $confidence,
                'factors' => json_encode($factors),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Update prediction with actual delivery time
     */
    public function recordActualDelivery(Shipment $shipment): void
    {
        if (!$shipment->delivered_at) {
            return;
        }

        $prediction = DB::table('delivery_predictions')
            ->where('shipment_id', $shipment->id)
            ->first();

        if ($prediction) {
            $predictedTime = Carbon::parse($prediction->predicted_delivery_at);
            $actualTime = Carbon::parse($shipment->delivered_at);
            $errorMinutes = $predictedTime->diffInMinutes($actualTime, false);

            DB::table('delivery_predictions')
                ->where('shipment_id', $shipment->id)
                ->update([
                    'actual_delivery_at' => $shipment->delivered_at,
                    'prediction_error_minutes' => $errorMinutes,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Get prediction accuracy statistics
     */
    public function getPredictionAccuracy(array $dateRange = []): array
    {
        $query = DB::table('delivery_predictions')
            ->whereNotNull('actual_delivery_at');

        if (!empty($dateRange['start'])) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if (!empty($dateRange['end'])) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_predictions,
            AVG(ABS(prediction_error_minutes)) as avg_error_minutes,
            AVG(confidence_score) as avg_confidence,
            SUM(CASE WHEN ABS(prediction_error_minutes) <= 60 THEN 1 ELSE 0 END) as within_1_hour,
            SUM(CASE WHEN ABS(prediction_error_minutes) <= 120 THEN 1 ELSE 0 END) as within_2_hours,
            SUM(CASE WHEN ABS(prediction_error_minutes) <= 240 THEN 1 ELSE 0 END) as within_4_hours
        ')->first();

        return [
            'total_predictions' => $stats->total_predictions ?? 0,
            'avg_error_minutes' => round($stats->avg_error_minutes ?? 0, 1),
            'avg_confidence' => round($stats->avg_confidence ?? 0, 1),
            'accuracy_within_1_hour' => $stats->total_predictions > 0 
                ? round(($stats->within_1_hour / $stats->total_predictions) * 100, 1) 
                : 0,
            'accuracy_within_2_hours' => $stats->total_predictions > 0 
                ? round(($stats->within_2_hours / $stats->total_predictions) * 100, 1) 
                : 0,
            'accuracy_within_4_hours' => $stats->total_predictions > 0 
                ? round(($stats->within_4_hours / $stats->total_predictions) * 100, 1) 
                : 0,
        ];
    }
}
