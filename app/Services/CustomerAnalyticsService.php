<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Shipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerAnalyticsService
{
    /**
     * Get comprehensive customer analytics
     */
    public function getCustomerAnalytics(Customer $customer, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'overview' => $this->getCustomerOverview($customer),
            'performance_metrics' => $this->getPerformanceMetrics($customer, $startDate),
            'financial_analysis' => $this->getFinancialAnalysis($customer, $startDate),
            'shipment_analysis' => $this->getShipmentAnalysis($customer, $startDate),
            'risk_assessment' => $this->getRiskAssessment($customer),
            'engagement_metrics' => $this->getEngagementMetrics($customer, $startDate),
            'predictive_insights' => $this->getPredictiveInsights($customer),
            'segmentation_data' => $this->getSegmentationData($customer),
        ];
    }

    /**
     * Get customer overview statistics
     */
    private function getCustomerOverview(Customer $customer): array
    {
        return [
            'customer_since' => $customer->customer_since?->format('Y-m-d'),
            'days_as_customer' => $customer->customer_since?->diffInDays(now()),
            'total_shipments' => $customer->total_shipments,
            'total_spent' => $customer->total_spent,
            'average_order_value' => $customer->average_order_value,
            'last_shipment_date' => $customer->last_shipment_date?->format('Y-m-d'),
            'days_since_last_shipment' => $customer->last_shipment_date?->diffInDays(now()),
            'current_balance' => $customer->current_balance,
            'available_credit' => $customer->available_credit,
            'credit_utilization' => $customer->credit_limit > 0 ? ($customer->current_balance / $customer->credit_limit) * 100 : 0,
            'risk_level' => $customer->getRiskLevel(),
            'satisfaction_score' => $customer->satisfaction_score,
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(Customer $customer, Carbon $startDate): array
    {
        $recentShipments = $customer->shipments()->where('created_at', '>=', $startDate)->get();

        $onTimeDeliveries = $recentShipments->where('current_status', 'delivered')
            ->where('delivered_at', '<=', DB::raw('expected_delivery_date'))
            ->count();

        $totalDelivered = $recentShipments->where('current_status', 'delivered')->count();
        $onTimeRate = $totalDelivered > 0 ? ($onTimeDeliveries / $totalDelivered) * 100 : 0;

        $complaints = $recentShipments->where('has_complaint', true)->count();
        $complaintRate = $recentShipments->count() > 0 ? ($complaints / $recentShipments->count()) * 100 : 0;

        return [
            'period_days' => now()->diffInDays($startDate),
            'total_shipments_period' => $recentShipments->count(),
            'on_time_delivery_rate' => round($onTimeRate, 1),
            'complaint_rate' => round($complaintRate, 1),
            'average_delivery_time' => $this->calculateAverageDeliveryTime($recentShipments),
            'shipment_frequency' => $this->calculateShipmentFrequency($customer, $startDate),
            'preferred_services' => $this->getPreferredServices($customer, $startDate),
            'peak_ordering_times' => $this->getPeakOrderingTimes($customer, $startDate),
        ];
    }

    /**
     * Get financial analysis
     */
    private function getFinancialAnalysis(Customer $customer, Carbon $startDate): array
    {
        $recentShipments = $customer->shipments()->where('created_at', '>=', $startDate)->get();

        $totalSpent = $recentShipments->sum('total_amount');
        $averageOrderValue = $recentShipments->avg('total_amount') ?: 0;

        $monthlySpending = $this->getMonthlySpending($customer, $startDate);

        return [
            'total_spent_period' => $totalSpent,
            'average_order_value_period' => round($averageOrderValue, 2),
            'monthly_spending_trend' => $monthlySpending,
            'payment_performance' => $this->getPaymentPerformance($customer, $startDate),
            'profitability_analysis' => $this->getProfitabilityAnalysis($customer, $startDate),
            'credit_health_score' => $this->calculateCreditHealthScore($customer),
            'lifetime_value' => $this->calculateLifetimeValue($customer),
        ];
    }

    /**
     * Get shipment analysis
     */
    private function getShipmentAnalysis(Customer $customer, Carbon $startDate): array
    {
        $shipments = $customer->shipments()->where('created_at', '>=', $startDate)->get();

        $statusDistribution = $shipments->groupBy('current_status')->map->count();

        $originDestinations = $shipments->groupBy(function ($shipment) {
            return $shipment->origin_branch_id . '-' . $shipment->destination_branch_id;
        })->map->count()->sortDesc()->take(5);

        return [
            'total_shipments' => $shipments->count(),
            'status_distribution' => $statusDistribution,
            'popular_routes' => $originDestinations,
            'service_type_preferences' => $this->getServiceTypePreferences($customer, $startDate),
            'weight_distribution' => $this->getWeightDistribution($customer, $startDate),
            'seasonal_patterns' => $this->getSeasonalPatterns($customer, $startDate),
            'delivery_performance' => $this->getDeliveryPerformance($customer, $startDate),
        ];
    }

    /**
     * Get risk assessment
     */
    private function getRiskAssessment(Customer $customer): array
    {
        $riskScore = 0;
        $riskFactors = [];

        // Credit risk
        $creditUtilization = $customer->credit_limit > 0 ? ($customer->current_balance / $customer->credit_limit) : 0;
        if ($creditUtilization > 0.9) {
            $riskScore += 3;
            $riskFactors[] = 'High credit utilization';
        } elseif ($creditUtilization > 0.7) {
            $riskScore += 2;
            $riskFactors[] = 'Moderate credit utilization';
        }

        // Payment history risk
        if ($customer->complaints_count > 10) {
            $riskScore += 3;
            $riskFactors[] = 'High complaint history';
        } elseif ($customer->complaints_count > 5) {
            $riskScore += 2;
            $riskFactors[] = 'Moderate complaint history';
        }

        // Recency risk
        $daysSinceLastShipment = $customer->last_shipment_date?->diffInDays(now()) ?? 999;
        if ($daysSinceLastShipment > 180) {
            $riskScore += 3;
            $riskFactors[] = 'Long time since last shipment';
        } elseif ($daysSinceLastShipment > 90) {
            $riskScore += 2;
            $riskFactors[] = 'Inactive for 3+ months';
        }

        // Shipment volume risk
        if ($customer->total_shipments < 5) {
            $riskScore += 1;
            $riskFactors[] = 'Low shipment history';
        }

        $riskLevel = match(true) {
            $riskScore >= 6 => 'high',
            $riskScore >= 3 => 'medium',
            default => 'low'
        };

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors,
            'recommended_actions' => $this->getRiskMitigationActions($riskLevel),
            'credit_health_score' => $this->calculateCreditHealthScore($customer),
        ];
    }

    /**
     * Get engagement metrics
     */
    private function getEngagementMetrics(Customer $customer, Carbon $startDate): array
    {
        $recentShipments = $customer->shipments()->where('created_at', '>=', $startDate)->count();
        $totalPeriodDays = now()->diffInDays($startDate);

        return [
            'engagement_score' => $this->calculateEngagementScore($customer, $startDate),
            'communication_frequency' => $this->getCommunicationFrequency($customer, $startDate),
            'response_times' => $this->getResponseTimes($customer, $startDate),
            'support_ticket_history' => $this->getSupportTicketHistory($customer, $startDate),
            'last_contact_date' => $customer->last_contact_date?->format('Y-m-d'),
            'preferred_contact_method' => $this->getPreferredContactMethod($customer),
            'feedback_history' => $this->getFeedbackHistory($customer, $startDate),
        ];
    }

    /**
     * Get predictive insights
     */
    private function getPredictiveInsights(Customer $customer): array
    {
        return [
            'churn_probability' => $this->calculateChurnProbability($customer),
            'next_shipment_prediction' => $this->predictNextShipment($customer),
            'expected_value_next_month' => $this->predictMonthlyValue($customer),
            'upsell_opportunities' => $this->identifyUpsellOpportunities($customer),
            'risk_trends' => $this->analyzeRiskTrends($customer),
            'seasonal_behavior' => $this->predictSeasonalBehavior($customer),
        ];
    }

    /**
     * Get segmentation data
     */
    private function getSegmentationData(Customer $customer): array
    {
        return [
            'current_segment' => $customer->segment,
            'rfm_score' => $this->calculateRFMScore($customer),
            'behavioral_segments' => $this->getBehavioralSegments($customer),
            'value_segments' => $this->getValueSegments($customer),
            'lifecycle_stage' => $this->determineLifecycleStage($customer),
            'segment_recommendations' => $this->getSegmentRecommendations($customer),
        ];
    }

    // Helper Methods

    private function calculateAverageDeliveryTime(Collection $shipments): float
    {
        $deliveredShipments = $shipments->where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('created_at');

        if ($deliveredShipments->isEmpty()) {
            return 0;
        }

        $totalHours = $deliveredShipments->sum(function ($shipment) {
            return $shipment->created_at->diffInHours($shipment->delivered_at);
        });

        return round($totalHours / $deliveredShipments->count(), 1);
    }

    private function calculateShipmentFrequency(Customer $customer, Carbon $startDate): array
    {
        $days = now()->diffInDays($startDate);
        $shipmentCount = $customer->shipments()->where('created_at', '>=', $startDate)->count();

        return [
            'shipments_per_day' => round($shipmentCount / $days, 2),
            'shipments_per_week' => round(($shipmentCount / $days) * 7, 1),
            'shipments_per_month' => round(($shipmentCount / $days) * 30, 1),
            'frequency_category' => $this->categorizeFrequency($shipmentCount, $days),
        ];
    }

    private function categorizeFrequency(int $count, int $days): string
    {
        $rate = $count / $days;

        if ($rate >= 1) return 'daily';
        if ($rate >= 0.3) return 'weekly';
        if ($rate >= 0.1) return 'biweekly';
        if ($rate >= 0.03) return 'monthly';
        return 'occasional';
    }

    private function getPreferredServices(Customer $customer, Carbon $startDate): array
    {
        return $customer->shipments()
            ->where('created_at', '>=', $startDate)
            ->select('service_type', DB::raw('count(*) as count'))
            ->groupBy('service_type')
            ->orderBy('count', 'desc')
            ->take(3)
            ->get()
            ->toArray();
    }

    private function getPeakOrderingTimes(Customer $customer, Carbon $startDate): array
    {
        $ordersByHour = $customer->shipments()
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'peak_hour' => $ordersByHour->first()?->hour,
            'peak_volume' => $ordersByHour->first()?->count,
            'hourly_distribution' => $ordersByHour->keyBy('hour')->toArray(),
        ];
    }

    private function getMonthlySpending(Customer $customer, Carbon $startDate): array
    {
        return $customer->shipments()
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => sprintf('%04d-%02d', $item->year, $item->month),
                    'amount' => $item->total,
                ];
            })
            ->toArray();
    }

    private function getPaymentPerformance(Customer $customer, Carbon $startDate): array
    {
        // This would integrate with payment/invoice system
        return [
            'on_time_payments' => 95.5, // Placeholder
            'average_payment_delay' => 2.3, // Placeholder
            'payment_methods_used' => ['bank_transfer', 'credit_card'], // Placeholder
        ];
    }

    private function getProfitabilityAnalysis(Customer $customer, Carbon $startDate): array
    {
        // This would calculate actual profitability
        return [
            'gross_margin' => 25.0, // Placeholder
            'net_profit_margin' => 12.5, // Placeholder
            'customer_acquisition_cost' => 150.00, // Placeholder
            'lifetime_value' => $this->calculateLifetimeValue($customer),
        ];
    }

    private function calculateCreditHealthScore(Customer $customer): float
    {
        $score = 100;

        // Deduct points for high utilization
        $utilization = $customer->credit_limit > 0 ? ($customer->current_balance / $customer->credit_limit) : 0;
        $score -= $utilization * 30;

        // Deduct points for complaints
        $score -= min($customer->complaints_count * 2, 20);

        // Deduct points for inactivity
        $daysSinceLast = $customer->last_shipment_date?->diffInDays(now()) ?? 999;
        if ($daysSinceLast > 90) {
            $score -= min(($daysSinceLast - 90) / 10, 20);
        }

        return max(0, round($score, 1));
    }

    private function calculateLifetimeValue(Customer $customer): float
    {
        $avgOrderValue = $customer->average_order_value ?: 0;
        $purchaseFrequency = $this->calculatePurchaseFrequency($customer);
        $customerLifespan = 2; // years, placeholder

        return round($avgOrderValue * $purchaseFrequency * $customerLifespan * 12, 2);
    }

    private function calculatePurchaseFrequency(Customer $customer): float
    {
        $daysAsCustomer = $customer->customer_since?->diffInDays(now()) ?? 1;
        return $customer->total_shipments / max(1, $daysAsCustomer / 30); // per month
    }

    private function getServiceTypePreferences(Customer $customer, Carbon $startDate): array
    {
        return $customer->shipments()
            ->where('created_at', '>=', $startDate)
            ->select('service_type', DB::raw('count(*) as count'))
            ->groupBy('service_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    private function getWeightDistribution(Customer $customer, Carbon $startDate): array
    {
        $weights = $customer->shipments()
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('weight')
            ->pluck('weight');

        return [
            'average_weight' => $weights->avg(),
            'median_weight' => $weights->median(),
            'weight_ranges' => $this->categorizeWeights($weights),
        ];
    }

    private function categorizeWeights(Collection $weights): array
    {
        return [
            'light' => $weights->filter(fn($w) => $w < 5)->count(),
            'medium' => $weights->filter(fn($w) => $w >= 5 && $w < 20)->count(),
            'heavy' => $weights->filter(fn($w) => $w >= 20)->count(),
        ];
    }

    private function getSeasonalPatterns(Customer $customer, Carbon $startDate): array
    {
        $monthlyData = $customer->shipments()
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        return [
            'monthly_distribution' => $monthlyData->toArray(),
            'peak_month' => $monthlyData->sortByDesc('count')->first()?->month,
            'seasonality_score' => $this->calculateSeasonalityScore($monthlyData),
        ];
    }

    private function calculateSeasonalityScore(Collection $monthlyData): float
    {
        if ($monthlyData->isEmpty()) return 0;

        $avg = $monthlyData->avg('count');
        $variance = $monthlyData->map(fn($m) => pow($m['count'] - $avg, 2))->avg();
        $stdDev = sqrt($variance);

        return $avg > 0 ? round(($stdDev / $avg) * 100, 1) : 0;
    }

    private function getDeliveryPerformance(Customer $customer, Carbon $startDate): array
    {
        $shipments = $customer->shipments()
            ->where('created_at', '>=', $startDate)
            ->where('current_status', 'delivered')
            ->get();

        $onTime = $shipments->filter(function ($shipment) {
            return $shipment->delivered_at && $shipment->expected_delivery_date &&
                   $shipment->delivered_at <= $shipment->expected_delivery_date;
        })->count();

        return [
            'on_time_deliveries' => $onTime,
            'total_deliveries' => $shipments->count(),
            'on_time_percentage' => $shipments->count() > 0 ? round(($onTime / $shipments->count()) * 100, 1) : 0,
            'average_delay' => $this->calculateAverageDelay($shipments),
        ];
    }

    private function calculateAverageDelay(Collection $shipments): float
    {
        $delayedShipments = $shipments->filter(function ($shipment) {
            return $shipment->delivered_at && $shipment->expected_delivery_date &&
                   $shipment->delivered_at > $shipment->expected_delivery_date;
        });

        if ($delayedShipments->isEmpty()) return 0;

        $totalDelayHours = $delayedShipments->sum(function ($shipment) {
            return $shipment->expected_delivery_date->diffInHours($shipment->delivered_at);
        });

        return round($totalDelayHours / $delayedShipments->count(), 1);
    }

    private function getRiskMitigationActions(string $riskLevel): array
    {
        return match($riskLevel) {
            'high' => [
                'Implement stricter credit controls',
                'Require prepayment for new orders',
                'Increase monitoring frequency',
                'Consider customer review meeting',
            ],
            'medium' => [
                'Monitor credit utilization closely',
                'Send payment reminders',
                'Offer payment plan options',
                'Review credit terms',
            ],
            'low' => [
                'Continue standard monitoring',
                'Consider credit limit increase',
                'Offer loyalty incentives',
            ],
        };
    }

    private function calculateEngagementScore(Customer $customer, Carbon $startDate): float
    {
        $score = 50; // Base score

        // Recency factor
        $daysSinceLast = $customer->last_shipment_date?->diffInDays(now()) ?? 999;
        if ($daysSinceLast < 30) $score += 20;
        elseif ($daysSinceLast < 90) $score += 10;
        elseif ($daysSinceLast > 180) $score -= 20;

        // Frequency factor
        $recentShipments = $customer->shipments()->where('created_at', '>=', $startDate)->count();
        $score += min($recentShipments * 2, 20);

        // Value factor
        if ($customer->total_spent > 10000) $score += 10;

        return max(0, min(100, $score));
    }

    private function getCommunicationFrequency(Customer $customer, Carbon $startDate): array
    {
        // This would track actual communications sent
        return [
            'emails_sent' => 0, // Placeholder
            'sms_sent' => 0, // Placeholder
            'calls_made' => 0, // Placeholder
        ];
    }

    private function getResponseTimes(Customer $customer, Carbon $startDate): array
    {
        // This would track response times to communications
        return [
            'average_response_time' => 0, // Placeholder in hours
            'response_rate' => 0, // Placeholder percentage
        ];
    }

    private function getSupportTicketHistory(Customer $customer, Carbon $startDate): array
    {
        // This would integrate with support ticket system
        return [
            'total_tickets' => $customer->complaints_count,
            'open_tickets' => 0, // Placeholder
            'average_resolution_time' => 0, // Placeholder in hours
        ];
    }

    private function getPreferredContactMethod(Customer $customer): string
    {
        $channels = $customer->communication_channels ?? ['email'];
        return $channels[0] ?? 'email';
    }

    private function getFeedbackHistory(Customer $customer, Carbon $startDate): array
    {
        // This would integrate with feedback/survey system
        return [
            'survey_responses' => 0, // Placeholder
            'average_rating' => $customer->satisfaction_score ?: 0,
            'feedback_themes' => [], // Placeholder
        ];
    }

    private function calculateChurnProbability(Customer $customer): float
    {
        $probability = 10; // Base 10% churn probability

        // Increase based on risk factors
        $daysSinceLast = $customer->last_shipment_date?->diffInDays(now()) ?? 999;
        if ($daysSinceLast > 180) $probability += 40;
        elseif ($daysSinceLast > 90) $probability += 20;

        if ($customer->complaints_count > 5) $probability += 15;

        return min(95, $probability);
    }

    private function predictNextShipment(Customer $customer): array
    {
        $frequency = $this->calculatePurchaseFrequency($customer);
        $daysBetweenOrders = 30 / max(0.1, $frequency); // Avoid division by zero

        $predictedDate = $customer->last_shipment_date?->addDays($daysBetweenOrders) ?? now();

        return [
            'predicted_date' => $predictedDate->format('Y-m-d'),
            'confidence_level' => $customer->total_shipments > 10 ? 'high' : 'medium',
            'days_until_prediction' => max(0, now()->diffInDays($predictedDate)),
        ];
    }

    private function predictMonthlyValue(Customer $customer): float
    {
        $recentAvg = $customer->shipments()
            ->where('created_at', '>=', now()->subDays(90))
            ->avg('total_amount') ?: 0;

        return round($recentAvg * $this->calculatePurchaseFrequency($customer), 2);
    }

    private function identifyUpsellOpportunities(Customer $customer): array
    {
        $opportunities = [];

        // Based on shipment patterns
        $avgWeight = $customer->shipments()->avg('weight') ?: 0;
        if ($avgWeight < 5) {
            $opportunities[] = 'Consider upgrading to larger package services';
        }

        // Based on frequency
        $frequency = $this->calculateShipmentFrequency($customer, now()->subDays(90));
        if ($frequency['shipments_per_month'] < 2) {
            $opportunities[] = 'Suggest subscription or bulk shipping plans';
        }

        return $opportunities;
    }

    private function analyzeRiskTrends(Customer $customer): array
    {
        // This would analyze historical risk data
        return [
            'trend' => 'stable', // improving, stable, declining
            'change_percentage' => 0,
            'key_factors' => [],
        ];
    }

    private function predictSeasonalBehavior(Customer $customer): array
    {
        $seasonalData = $this->getSeasonalPatterns($customer, now()->subDays(365));

        return [
            'predicted_peak_months' => [12, 1, 2], // Example: winter months
            'seasonal_multiplier' => 1.5, // 50% increase during peak
            'recommended_actions' => ['Stock up inventory', 'Prepare for increased volume'],
        ];
    }

    private function calculateRFMScore(Customer $customer): array
    {
        // RFM Analysis: Recency, Frequency, Monetary
        $recency = $customer->last_shipment_date?->diffInDays(now()) ?? 999;
        $frequency = $customer->total_shipments;
        $monetary = $customer->total_spent;

        // Score each component (1-5 scale, 5 being best)
        $rScore = match(true) {
            $recency <= 30 => 5,
            $recency <= 60 => 4,
            $recency <= 120 => 3,
            $recency <= 180 => 2,
            default => 1,
        };

        $fScore = match(true) {
            $frequency >= 50 => 5,
            $frequency >= 20 => 4,
            $frequency >= 10 => 3,
            $frequency >= 5 => 2,
            default => 1,
        };

        $mScore = match(true) {
            $monetary >= 50000 => 5,
            $monetary >= 20000 => 4,
            $monetary >= 10000 => 3,
            $monetary >= 5000 => 2,
            default => 1,
        };

        return [
            'recency_score' => $rScore,
            'frequency_score' => $fScore,
            'monetary_score' => $mScore,
            'rfm_segment' => $rScore . $fScore . $mScore,
            'overall_score' => round(($rScore + $fScore + $mScore) / 3, 1),
        ];
    }

    private function getBehavioralSegments(Customer $customer): array
    {
        $segments = [];

        if ($customer->total_shipments > 100) {
            $segments[] = 'High Volume';
        }

        $avgOrderValue = $customer->average_order_value ?: 0;
        if ($avgOrderValue > 1000) {
            $segments[] = 'High Value';
        }

        $frequency = $this->calculateShipmentFrequency($customer, now()->subDays(90));
        if ($frequency['shipments_per_month'] > 4) {
            $segments[] = 'Frequent Shipper';
        }

        return $segments;
    }

    private function getValueSegments(Customer $customer): array
    {
        $lifetimeValue = $this->calculateLifetimeValue($customer);

        if ($lifetimeValue > 100000) return ['Platinum'];
        if ($lifetimeValue > 50000) return ['Gold'];
        if ($lifetimeValue > 25000) return ['Silver'];
        return ['Bronze'];
    }

    private function determineLifecycleStage(Customer $customer): string
    {
        $daysAsCustomer = $customer->customer_since?->diffInDays(now()) ?? 0;
        $totalShipments = $customer->total_shipments;

        if ($daysAsCustomer < 30) return 'New';
        if ($totalShipments < 3) return 'Trial';
        if ($daysAsCustomer < 180) return 'Growing';
        if ($totalShipments > 50) return 'Mature';
        return 'Established';
    }

    private function getSegmentRecommendations(Customer $customer): array
    {
        $recommendations = [];
        $lifecycleStage = $this->determineLifecycleStage($customer);

        switch ($lifecycleStage) {
            case 'New':
                $recommendations[] = 'Send welcome package and onboarding materials';
                $recommendations[] = 'Offer discounted first shipment';
                break;
            case 'Trial':
                $recommendations[] = 'Provide usage tutorials and tips';
                $recommendations[] = 'Schedule follow-up call';
                break;
            case 'Growing':
                $recommendations[] = 'Introduce premium services';
                $recommendations[] = 'Offer volume discounts';
                break;
            case 'Mature':
                $recommendations[] = 'Focus on retention and loyalty programs';
                $recommendations[] = 'Consider account management upgrade';
                break;
        }

        return $recommendations;
    }
}