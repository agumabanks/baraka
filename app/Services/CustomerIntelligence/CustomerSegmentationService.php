<?php

namespace App\Services\CustomerIntelligence;

use App\Models\ETL\FactCustomerSegments;
use App\Models\ETL\FactShipment;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\ETL\FactCustomerChurnMetrics;
use App\Models\ETL\DimensionCustomerSegments;
use App\Models\ETL\DimensionClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerSegmentationService
{
    /**
     * Generate comprehensive customer segmentation for a specific customer
     */
    public function generateCustomerSegmentation(int $clientKey, int $analysisPeriod = 90): array
    {
        $customerData = $this->collectCustomerData($clientKey, $analysisPeriod);
        
        if (empty($customerData)) {
            return $this->createDefaultSegmentation($clientKey);
        }

        // Calculate RFM scores
        $rfmScores = $this->calculateRfmScores($customerData);
        
        // Determine volume tier
        $volumeTier = $this->determineVolumeTier($customerData);
        
        // Determine profitability tier
        $profitabilityTier = $this->determineProfitabilityTier($customerData);
        
        // Determine behavioral segment
        $behavioralSegment = $this->determineBehavioralSegment($customerData);
        
        // Determine lifecycle stage
        $lifecycleStage = $this->determineLifecycleStage($customerData, $clientKey);
        
        // Calculate various scores
        $valueScore = $this->calculateValueScore($customerData);
        $engagementScore = $this->calculateEngagementScore($customerData);
        $loyaltyScore = $this->calculateLoyaltyScore($customerData, $rfmScores);
        $growthPotential = $this->calculateGrowthPotential($customerData);
        $retentionRisk = $this->calculateRetentionRisk($customerData);
        
        // Identify opportunities
        $upsellOpportunities = $this->identifyUpsellOpportunities($customerData, $behavioralSegment);
        $crossSellOpportunities = $this->identifyCrossSellOpportunities($customerData, $behavioralSegment);
        
        // Determine primary segment
        $primarySegment = $this->determinePrimarySegment($volumeTier, $profitabilityTier, $behavioralSegment);
        
        $segmentation = [
            'client_key' => $clientKey,
            'primary_segment' => $primarySegment,
            'secondary_segments' => $this->getSecondarySegments($volumeTier, $profitabilityTier, $behavioralSegment),
            'volume_tier' => $volumeTier,
            'profitability_tier' => $profitabilityTier,
            'behavioral_segment' => $behavioralSegment,
            'lifecycle_stage' => $lifecycleStage,
            'rfm_score' => $rfmScores['overall_score'],
            'value_score' => $valueScore,
            'engagement_score' => $engagementScore,
            'loyalty_score' => $loyaltyScore,
            'growth_potential' => $growthPotential,
            'retention_risk' => $retentionRisk,
            'upsell_opportunities' => $upsellOpportunities,
            'cross_sell_opportunities' => $crossSellOpportunities,
            'preferred_communication_channel' => $this->determinePreferredChannel($customerData),
            'segment_characteristics' => $this->getSegmentCharacteristics($volumeTier, $profitabilityTier, $behavioralSegment),
            'segment_changes' => $this->analyzeSegmentChanges($clientKey),
            'recommendations' => $this->getSegmentationRecommendations($primarySegment, $customerData),
            'model_version' => '1.0'
        ];

        // Store segmentation in fact table
        $this->storeSegmentation($clientKey, $segmentation);

        return $segmentation;
    }

    /**
     * Batch update all customer segmentations
     */
    public function batchUpdateAllSegmentations(): array
    {
        $updated = 0;
        $errors = [];

        try {
            $clientKeys = DB::table('dimension_clients')
                ->where('is_active', true)
                ->pluck('client_key');

            foreach ($clientKeys as $clientKey) {
                try {
                    $this->generateCustomerSegmentation($clientKey);
                    $updated++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'client_key' => $clientKey,
                        'error' => $e->getMessage()
                    ];
                }
            }
        } catch (\Exception $e) {
            $errors[] = ['batch_error' => $e->getMessage()];
        }

        return [
            'total_processed' => $updated,
            'errors' => $errors,
            'processed_at' => now()
        ];
    }

    /**
     * Get high-value customers for marketing focus
     */
    public function getHighValueCustomers(int $limit = 50): Collection
    {
        $highValueCustomers = FactCustomerSegments::where('value_score', '>=', 0.8)
            ->orderBy('value_score', 'desc')
            ->limit($limit)
            ->with('client')
            ->get();

        return $highValueCustomers->map(function ($customer) {
            return [
                'client_key' => $customer->client_key,
                'client_name' => $customer->client->client_name ?? 'Unknown',
                'value_score' => $customer->value_score,
                'primary_segment' => $customer->primary_segment,
                'volume_tier' => $customer->volume_tier,
                'profitability_tier' => $customer->profitability_tier,
                'upsell_opportunities' => $customer->upsell_opportunities,
                'retention_risk' => $customer->retention_risk
            ];
        });
    }

    /**
     * Get customers with high growth potential
     */
    public function getHighGrowthPotentialCustomers(int $limit = 50): Collection
    {
        return FactCustomerSegments::where('growth_potential', '>=', 0.7)
            ->where('retention_risk', '<', 0.3)
            ->orderBy('growth_potential', 'desc')
            ->limit($limit)
            ->with('client')
            ->get()
            ->map(function ($customer) {
                return [
                    'client_key' => $customer->client_key,
                    'client_name' => $customer->client->client_name ?? 'Unknown',
                    'growth_potential' => $customer->growth_potential,
                    'current_segment' => $customer->primary_segment,
                    'recommended_focus' => $customer->upsell_opportunities,
                    'retention_risk' => $customer->retention_risk
                ];
            });
    }

    /**
     * Get at-risk customers requiring immediate attention
     */
    public function getAtRiskCustomers(int $limit = 50): Collection
    {
        return FactCustomerSegments::where('retention_risk', '>=', 0.7)
            ->orderBy('retention_risk', 'desc')
            ->limit($limit)
            ->with('client')
            ->get()
            ->map(function ($customer) {
                return [
                    'client_key' => $customer->client_key,
                    'client_name' => $customer->client->client_name ?? 'Unknown',
                    'retention_risk' => $customer->retention_risk,
                    'risk_factors' => $this->identifyRiskFactors($customer),
                    'recommended_actions' => $this->getRetentionActions($customer->retention_risk),
                    'priority_level' => $this->getRiskPriority($customer->retention_risk)
                ];
            });
    }

    /**
     * Analyze segment distribution and trends
     */
    public function getSegmentAnalysis(): array
    {
        $segments = FactCustomerSegments::with('client')->get();

        return [
            'segment_distribution' => $this->getSegmentDistribution($segments),
            'volume_tier_distribution' => $this->getVolumeTierDistribution($segments),
            'profitability_distribution' => $this->getProfitabilityDistribution($segments),
            'lifecycle_stage_distribution' => $this->getLifecycleDistribution($segments),
            'segment_performance' => $this->analyzeSegmentPerformance($segments),
            'segment_trends' => $this->analyzeSegmentTrends($segments)
        ];
    }

    /**
     * Get personalized recommendations for a specific customer segment
     */
    public function getSegmentRecommendations(string $segmentType, array $criteria = []): array
    {
        $dimensionSegment = DimensionCustomerSegments::where('segment_type', $segmentType)
            ->where('is_active', true)
            ->first();

        if (!$dimensionSegment) {
            return [];
        }

        return [
            'segment_info' => $dimensionSegment,
            'targeting_criteria' => $dimensionSegment->targeting_criteria,
            'marketing_messaging' => $dimensionSegment->marketing_messaging,
            'retention_strategies' => $dimensionSegment->retention_strategies,
            'upsell_opportunities' => $dimensionSegment->upsell_opportunities,
            'cross_sell_opportunities' => $dimensionSegment->cross_sell_opportunities,
            'priority_level' => $dimensionSegment->priority_level
        ];
    }

    private function collectCustomerData(int $clientKey, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        $startDateKey = $startDate->format('Ymd');

        // Get shipment data
        $shipmentData = FactShipment::where('client_key', $clientKey)
            ->where('pickup_date_key', '>=', $startDateKey)
            ->get();

        // Get financial data
        $financialData = FactFinancialTransaction::where('client_key', $clientKey)
            ->where('transaction_date_key', '>=', $startDateKey)
            ->get();

        // Get churn risk data
        $churnData = FactCustomerChurnMetrics::where('client_key', $clientKey)
            ->orderBy('churn_date_key', 'desc')
            ->first();

        return [
            'shipments' => $shipmentData,
            'financial' => $financialData,
            'churn' => $churnData,
            'analysis_period' => $days,
            'data_completeness' => $this->assessDataCompleteness($shipmentData, $financialData)
        ];
    }

    private function calculateRfmScores(array $customerData): array
    {
        $shipments = $customerData['shipments'];
        $financial = $customerData['financial'];

        if ($shipments->isEmpty() && $financial->isEmpty()) {
            return [
                'recency_score' => 1,
                'frequency_score' => 1,
                'monetary_score' => 1,
                'overall_score' => 1.0
            ];
        }

        // Recency: days since last activity
        $lastActivity = $shipments->max('pickup_date_key') ?? $financial->max('transaction_date_key');
        $daysSinceLastActivity = $lastActivity 
            ? Carbon::createFromFormat('Ymd', $lastActivity)->diffInDays(now())
            : 999;

        $recencyScore = match(true) {
            $daysSinceLastActivity <= 30 => 5,
            $daysSinceLastActivity <= 60 => 4,
            $daysSinceLastActivity <= 120 => 3,
            $daysSinceLastActivity <= 180 => 2,
            default => 1
        };

        // Frequency: number of transactions/shipments
        $totalTransactions = $shipments->count() + $financial->count();
        $frequencyScore = match(true) {
            $totalTransactions >= 50 => 5,
            $totalTransactions >= 20 => 4,
            $totalTransactions >= 10 => 3,
            $totalTransactions >= 5 => 2,
            default => 1
        ];

        // Monetary: total value
        $totalMonetaryValue = $shipments->sum('revenue') + $financial->sum('transaction_amount');
        $monetaryScore = match(true) {
            $totalMonetaryValue >= 50000 => 5,
            $totalMonetaryValue >= 20000 => 4,
            $totalMonetaryValue >= 10000 => 3,
            $totalMonetaryValue >= 5000 => 2,
            default => 1
        };

        $overallScore = ($recencyScore + $frequencyScore + $monetaryScore) / 3;

        return [
            'recency_score' => $recencyScore,
            'frequency_score' => $frequencyScore,
            'monetary_score' => $monetaryScore,
            'overall_score' => round($overallScore, 1)
        ];
    }

    private function determineVolumeTier(array $customerData): string
    {
        $shipments = $customerData['shipments'];
        $monthlyVolume = $shipments->count() / max(1, $customerData['analysis_period'] / 30);

        return match(true) {
            $monthlyVolume >= 200 => 'enterprise',
            $monthlyVolume >= 50 => 'high',
            $monthlyVolume >= 10 => 'medium',
            default => 'low'
        };
    }

    private function determineProfitabilityTier(array $customerData): string
    {
        $shipments = $customerData['shipments'];
        $financial = $customerData['financial'];

        $totalRevenue = $shipments->sum('revenue') + $financial->sum('transaction_amount');
        $totalCost = $shipments->sum('total_cost') + $financial->sum('transaction_amount') * 0.1; // Assume 10% cost
        $profitMargin = $totalRevenue > 0 ? ($totalRevenue - $totalCost) / $totalRevenue : 0;

        return match(true) {
            $profitMargin >= 0.25 => 'premium',
            $profitMargin >= 0.15 => 'high',
            $profitMargin >= 0.08 => 'medium',
            default => 'low'
        };
    }

    private function determineBehavioralSegment(array $customerData): string
    {
        $shipments = $customerData['shipments'];
        $financial = $customerData['financial'];

        if ($shipments->isEmpty() && $financial->isEmpty()) {
            return 'inactive';
        }

        $totalValue = $shipments->sum('revenue') + $financial->sum('transaction_amount');
        $avgOrderValue = $totalValue / max(1, $shipments->count() + $financial->count());
        $frequency = ($shipments->count() + $financial->count()) / max(1, $customerData['analysis_period'] / 30);

        // Check for seasonal patterns
        $monthlyDistribution = $shipments->groupBy(function($shipment) {
            return Carbon::createFromFormat('Ymd', $shipment->pickup_date_key)->format('m');
        });
        $seasonalVariance = $this->calculateSeasonalVariance($monthlyDistribution);

        $segments = [];
        if ($avgOrderValue > 1000) $segments[] = 'high_value_orders';
        if ($frequency > 4) $segments[] = 'frequent_shipper';
        if ($seasonalVariance > 0.5) $segments[] = 'seasonal_shipper';
        if ($shipments->where('current_status', 'delivered')->count() / max(1, $shipments->count()) > 0.9) {
            $segments[] = 'reliable_customer';
        }

        return !empty($segments) ? implode('_', $segments) : 'standard_customer';
    }

    private function determineLifecycleStage(array $customerData, int $clientKey): string
    {
        $client = DimensionClient::where('client_key', $clientKey)->first();
        if (!$client || !$client->customer_since) {
            return 'new';
        }

        $daysAsCustomer = $client->customer_since->diffInDays(now());
        $shipments = $customerData['shipments'];

        return match(true) {
            $daysAsCustomer <= 30 => 'new',
            $daysAsCustomer <= 90 => 'trial',
            $daysAsCustomer <= 180 => 'growing',
            $shipments->count() >= 50 => 'mature',
            $daysAsCustomer >= 365 => 'established',
            default => 'growing'
        };
    }

    private function calculateValueScore(array $customerData): float
    {
        $shipments = $customerData['shipments'];
        $financial = $customerData['financial'];

        $totalRevenue = $shipments->sum('revenue') + $financial->sum('transaction_amount');
        $frequency = ($shipments->count() + $financial->count()) / max(1, $customerData['analysis_period'] / 30);
        $consistency = $this->calculateConsistency($customerData);

        $valueScore = (
            (min($totalRevenue / 10000, 1) * 0.4) + // Revenue component
            (min($frequency / 10, 1) * 0.3) + // Frequency component
            ($consistency * 0.3) // Consistency component
        );

        return round($valueScore, 4);
    }

    private function calculateEngagementScore(array $customerData): float
    {
        $shipments = $customerData['shipments'];
        $daysActive = $this->calculateDaysActive($customerData);
        $onTimeRate = $this->calculateOnTimeRate($customerData);

        $engagementScore = (
            (min($daysActive / 30, 1) * 0.4) + // Activity level
            (min($shipments->count() / 20, 1) * 0.3) + // Volume engagement
            ($onTimeRate * 0.3) // Reliability engagement
        );

        return round($engagementScore, 4);
    }

    private function calculateLoyaltyScore(array $customerData, array $rfmScores): float
    {
        $churnData = $customerData['churn'];
        $churnProbability = $churnData ? $churnData->churn_probability : 0.5;
        $retentionScore = 1 - $churnProbability;

        $loyaltyScore = (
            ($rfmScores['overall_score'] / 5) * 0.5 + // RFM component
            ($retentionScore) * 0.3 + // Retention component
            (min($customerData['analysis_period'] / 365, 1) * 0.2) // Tenure component
        );

        return round($loyaltyScore, 4);
    }

    private function calculateGrowthPotential(array $customerData): float
    {
        $shipments = $customerData['shipments'];
        $churnData = $customerData['churn'];
        $engagementScore = $this->calculateEngagementScore($customerData);

        $retentionRisk = $churnData ? $churnData->retention_risk : 0.5;
        $volumeGrowth = min($shipments->count() / 50, 1); // Normalize against expected volume

        $growthPotential = (
            ($engagementScore) * 0.4 + // Engagement component
            ($volumeGrowth) * 0.3 + // Volume potential
            (1 - $retentionRisk) * 0.3 // Retention component
        );

        return round($growthPotential, 4);
    }

    private function calculateRetentionRisk(array $customerData): float
    {
        $churnData = $customerData['churn'];
        if ($churnData) {
            return $churnData->retention_risk;
        }

        // Fallback calculation based on available data
        $shipments = $customerData['shipments'];
        $daysSinceLastShipment = $shipments->isEmpty() ? 999 : 
            Carbon::createFromFormat('Ymd', $shipments->max('pickup_date_key'))->diffInDays(now());

        $riskScore = 0;
        if ($daysSinceLastShipment > 30) $riskScore += 0.3;
        if ($shipments->count() < 5) $riskScore += 0.4;
        if ($customerData['analysis_period'] > 90 && $shipments->count() == 0) $riskScore += 0.3;

        return round(min($riskScore, 1.0), 4);
    }

    private function identifyUpsellOpportunities(array $customerData, string $behavioralSegment): array
    {
        $opportunities = [];
        $shipments = $customerData['shipments'];

        if ($shipments->avg('weight_lbs') < 5) {
            $opportunities[] = 'Consider larger package services';
        }

        if ($shipments->count() > 20 && $customerData['analysis_period'] <= 90) {
            $opportunities[] = 'Volume discounts for high-frequency shipper';
        }

        if (strpos($behavioralSegment, 'seasonal') !== false) {
            $opportunities[] = 'Seasonal shipping plans';
        }

        if ($shipments->where('on_time_indicator', false)->count() / max(1, $shipments->count()) > 0.1) {
            $opportunities[] = 'Express delivery services';
        }

        return $opportunities;
    }

    private function identifyCrossSellOpportunities(array $customerData, string $behavioralSegment): array
    {
        $opportunities = [];
        $shipments = $customerData['shipments'];

        $opportunities[] = 'Tracking and insurance services';
        $opportunities[] = 'Packaging solutions';

        if (strpos($behavioralSegment, 'high_value') !== false) {
            $opportunities[] = 'Premium support services';
            $opportunities[] = 'Dedicated account management';
        }

        if ($shipments->count() > 10) {
            $opportunities[] = 'API integration for automation';
        }

        return $opportunities;
    }

    private function determinePrimarySegment(string $volumeTier, string $profitabilityTier, string $behavioralSegment): string
    {
        // Priority-based segment determination
        if ($profitabilityTier === 'premium' && $volumeTier === 'enterprise') {
            return 'enterprise_premium';
        }

        if ($volumeTier === 'enterprise') {
            return 'enterprise_' . $profitabilityTier;
        }

        if ($profitabilityTier === 'premium') {
            return 'premium_' . $behavioralSegment;
        }

        return $volumeTier . '_' . $behavioralSegment;
    }

    private function getSecondarySegments(string $volumeTier, string $profitabilityTier, string $behavioralSegment): array
    {
        $segments = [];
        
        if ($volumeTier !== 'low') {
            $segments[] = 'volume_customer';
        }
        
        if ($profitabilityTier !== 'low') {
            $segments[] = 'profitable_customer';
        }
        
        if ($behavioralSegment !== 'standard_customer') {
            $segments[] = $behavioralSegment;
        }

        return $segments;
    }

    private function determinePreferredChannel(array $customerData): string
    {
        // This would ideally analyze actual communication data
        // For now, use behavioral patterns
        $shipments = $customerData['shipments'];
        
        if ($shipments->count() > 20) {
            return 'api_integration';
        }
        
        if ($shipments->count() > 5) {
            return 'email';
        }
        
        return 'phone';
    }

    private function getSegmentCharacteristics(string $volumeTier, string $profitabilityTier, string $behavioralSegment): array
    {
        return [
            'volume_level' => $volumeTier,
            'profitability_level' => $profitabilityTier,
            'behavioral_pattern' => $behavioralSegment,
            'key_traits' => $this->getSegmentTraits($volumeTier, $profitabilityTier, $behavioralSegment)
        ];
    }

    private function analyzeSegmentChanges(int $clientKey): array
    {
        // Get previous segmentation if available
        $previousSegmentation = FactCustomerSegments::where('client_key', $clientKey)
            ->orderBy('segment_date_key', 'desc')
            ->skip(1)
            ->first();

        if (!$previousSegmentation) {
            return ['change_type' => 'initial_segmentation'];
        }

        $currentSegmentation = FactCustomerSegments::where('client_key', $clientKey)
            ->orderBy('segment_date_key', 'desc')
            ->first();

        $changes = [];
        
        if ($currentSegmentation->primary_segment !== $previousSegmentation->primary_segment) {
            $changes[] = 'primary_segment_changed';
        }
        
        if ($currentSegmentation->volume_tier !== $previousSegmentation->volume_tier) {
            $changes[] = 'volume_tier_changed';
        }
        
        if ($currentSegmentation->profitability_tier !== $previousSegmentation->profitability_tier) {
            $changes[] = 'profitability_tier_changed';
        }

        return [
            'change_type' => !empty($changes) ? 'segment_evolution' : 'stable',
            'changes' => $changes,
            'previous_segment' => $previousSegmentation->primary_segment,
            'current_segment' => $currentSegmentation->primary_segment
        ];
    }

    private function getSegmentationRecommendations(string $primarySegment, array $customerData): array
    {
        $recommendations = [];
        
        switch ($primarySegment) {
            case 'enterprise_premium':
                $recommendations[] = 'Provide dedicated account management';
                $recommendations[] = 'Offer custom service agreements';
                break;
            case 'high_volume':
                $recommendations[] = 'Implement volume-based pricing';
                $recommendations[] = 'Provide automation tools';
                break;
            case 'premium':
                $recommendations[] = 'Focus on service quality and reliability';
                $recommendations[] = 'Offer premium support features';
                break;
        }
        
        return $recommendations;
    }

    private function storeSegmentation(int $clientKey, array $segmentation): void
    {
        FactCustomerSegments::updateOrCreate(
            ['client_key' => $clientKey],
            array_merge($segmentation, [
                'segment_key' => $this->generateSegmentKey($clientKey),
                'segment_date_key' => now()->format('Ymd')
            ])
        );
    }

    private function generateSegmentKey(int $clientKey): string
    {
        return $clientKey . '_' . now()->format('Ymd');
    }

    private function createDefaultSegmentation(int $clientKey): array
    {
        return [
            'client_key' => $clientKey,
            'primary_segment' => 'new_customer',
            'secondary_segments' => [],
            'volume_tier' => 'low',
            'profitability_tier' => 'low',
            'behavioral_segment' => 'new',
            'lifecycle_stage' => 'new',
            'rfm_score' => 1.0,
            'value_score' => 0.1,
            'engagement_score' => 0.1,
            'loyalty_score' => 0.5,
            'growth_potential' => 0.5,
            'retention_risk' => 0.5,
            'upsell_opportunities' => ['Onboarding assistance'],
            'cross_sell_opportunities' => ['Basic services'],
            'preferred_communication_channel' => 'email',
            'segment_characteristics' => ['new_customer'],
            'segment_changes' => ['initial_segmentation'],
            'recommendations' => ['Focus on onboarding and engagement'],
            'model_version' => '1.0'
        ];
    }

    // Additional helper methods for advanced analysis...
    
    private function calculateSeasonalVariance($monthlyDistribution): float
    {
        if ($monthlyDistribution->count() < 2) return 0;
        
        $values = $monthlyDistribution->map->count()->values();
        $mean = $values->avg();
        $variance = $values->map(fn($v) => pow($v - $mean, 2))->avg();
        
        return $mean > 0 ? sqrt($variance) / $mean : 0;
    }

    private function assessDataCompleteness($shipments, $financial): float
    {
        $totalExpected = 90; // Expected days of activity
        $hasShipmentData = $shipments->isNotEmpty();
        $hasFinancialData = $financial->isNotEmpty();
        
        $completeness = 0;
        if ($hasShipmentData) $completeness += 0.5;
        if ($hasFinancialData) $completeness += 0.5;
        
        return $completeness;
    }

    private function calculateConsistency(array $customerData): float
    {
        $shipments = $customerData['shipments'];
        if ($shipments->count() < 2) return 0.5;
        
        // Calculate coefficient of variation for shipment frequency
        $dates = $shipments->pluck('pickup_date_key')->map(function($date) {
            return Carbon::createFromFormat('Ymd', $date);
        })->sort();
        
        $intervals = [];
        for ($i = 1; $i < $dates->count(); $i++) {
            $intervals[] = $dates[$i]->diffInDays($dates[$i-1]);
        }
        
        if (empty($intervals)) return 0.5;
        
        $mean = array_sum($intervals) / count($intervals);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $intervals)) / count($intervals);
        $stdDev = sqrt($variance);
        
        return $mean > 0 ? max(0, 1 - ($stdDev / $mean)) : 0;
    }

    private function calculateDaysActive(array $customerData): int
    {
        $shipments = $customerData['shipments'];
        if ($shipments->isEmpty()) return 0;
        
        $dates = $shipments->pluck('pickup_date_key')->map(function($date) {
            return Carbon::createFromFormat('Ymd', $date)->format('Y-m-d');
        })->unique();
        
        return $dates->count();
    }

    private function calculateOnTimeRate(array $customerData): float
    {
        $shipments = $customerData['shipments'];
        if ($shipments->isEmpty()) return 0;
        
        $onTimeShipments = $shipments->where('on_time_indicator', true)->count();
        return $onTimeShipments / $shipments->count();
    }

    private function getSegmentTraits($volumeTier, $profitabilityTier, $behavioralSegment): array
    {
        $traits = [];
        
        $volumeTraits = [
            'low' => 'Occasional shipper',
            'medium' => 'Regular shipper',
            'high' => 'Frequent shipper',
            'enterprise' => 'Large volume shipper'
        ];
        
        $profitabilityTraits = [
            'low' => 'Price sensitive',
            'medium' => 'Balanced value seeker',
            'high' => 'Quality focused',
            'premium' => 'Premium service seeker'
        ];
        
        $traits[] = $volumeTraits[$volumeTier] ?? 'Unknown volume pattern';
        $traits[] = $profitabilityTraits[$profitabilityTier] ?? 'Unknown profitability pattern';
        $traits[] = ucwords(str_replace('_', ' ', $behavioralSegment));
        
        return $traits;
    }

    private function identifyRiskFactors($customer): array
    {
        $factors = [];
        
        if ($customer->retention_risk > 0.7) {
            $factors[] = 'High retention risk';
        }
        
        if ($customer->engagement_score < 0.3) {
            $factors[] = 'Low engagement';
        }
        
        if ($customer->days_since_last_shipment > 30) {
            $factors[] = 'Inactivity';
        }
        
        return $factors;
    }

    private function getRetentionActions($retentionRisk): array
    {
        if ($retentionRisk > 0.8) {
            return [
                'Immediate personal outreach',
                'Emergency retention package',
                'Executive escalation'
            ];
        } elseif ($retentionRisk > 0.6) {
            return [
                'Proactive customer service',
                'Loyalty incentives',
                'Service improvement plan'
            ];
        } else {
            return [
                'Regular check-ins',
                'Continuous engagement',
                'Monitor for early warning signs'
            ];
        }
    }

    private function getRiskPriority($retentionRisk): string
    {
        return match(true) {
            $retentionRisk > 0.8 => 'critical',
            $retentionRisk > 0.6 => 'high',
            $retentionRisk > 0.4 => 'medium',
            default => 'low'
        };
    }

    private function getSegmentDistribution($segments): array
    {
        return $segments->groupBy('primary_segment')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->toArray();
    }

    private function getVolumeTierDistribution($segments): array
    {
        return $segments->groupBy('volume_tier')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getProfitabilityDistribution($segments): array
    {
        return $segments->groupBy('profitability_tier')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getLifecycleDistribution($segments): array
    {
        return $segments->groupBy('lifecycle_stage')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function analyzeSegmentPerformance($segments): array
    {
        return $segments->groupBy('primary_segment')
            ->map(function($group) {
                return [
                    'segment' => $group->first()->primary_segment,
                    'count' => $group->count(),
                    'avg_value_score' => round($group->avg('value_score'), 2),
                    'avg_retention_risk' => round($group->avg('retention_risk'), 2),
                    'avg_growth_potential' => round($group->avg('growth_potential'), 2)
                ];
            })
            ->sortByDesc('avg_value_score')
            ->values()
            ->toArray();
    }

    private function analyzeSegmentTrends($segments): array
    {
        // Implementation for trend analysis over time
        return [];
    }
}
