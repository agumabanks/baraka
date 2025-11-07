<?php

namespace App\Services\CustomerIntelligence;

use App\Models\ETL\FactCustomerSatisfactionMetrics;
use App\Models\ETL\FactShipment;
use App\Models\ETL\FactCustomerSentiment;
use App\Models\ETL\FactCustomerChurnMetrics;
use App\Models\Backend\Support;
use App\Models\Backend\SupportChat;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerSatisfactionService
{
    /**
     * Calculate comprehensive customer satisfaction metrics with issue categorization
     */
    public function calculateCustomerSatisfactionMetrics(int $clientKey, int $analysisPeriod = 90): array
    {
        $satisfactionData = $this->collectSatisfactionData($clientKey, $analysisPeriod);
        
        if (empty($satisfactionData)) {
            return $this->createDefaultSatisfactionMetrics($clientKey);
        }

        // Calculate various satisfaction metrics
        $multiDimensionalScoring = $this->calculateMultiDimensionalSatisfactionScoring($satisfactionData);
        $issueCategorization = $this->performIssueCategorization($satisfactionData);
        $satisfactionTrendAnalysis = $this->analyzeSatisfactionTrends($satisfactionData, $analysisPeriod);
        $rootCauseAnalysis = $this->performRootCauseAnalysis($satisfactionData, $multiDimensionalScoring);
        $improvementOpportunities = $this->identifyImprovementOpportunities($satisfactionData, $issueCategorization);
        $satisfactionBenchmarking = $this->performSatisfactionBenchmarking($multiDimensionalScoring);
        $predictiveSatisfaction = $this->calculatePredictiveSatisfaction($satisfactionData);
        
        $satisfactionAnalysis = [
            'client_key' => $clientKey,
            'analysis_period_days' => $analysisPeriod,
            'multi_dimensional_scoring' => $multiDimensionalScoring,
            'issue_categorization' => $issueCategorization,
            'satisfaction_trend_analysis' => $satisfactionTrendAnalysis,
            'root_cause_analysis' => $rootCauseAnalysis,
            'improvement_opportunities' => $improvementOpportunities,
            'satisfaction_benchmarking' => $satisfactionBenchmarking,
            'predictive_satisfaction' => $predictiveSatisfaction,
            'satisfaction_health_score' => $this->calculateSatisfactionHealthScore($multiDimensionalScoring, $satisfactionTrendAnalysis),
            'satisfaction_insights' => $this->generateSatisfactionInsights($multiDimensionalScoring, $issueCategorization),
            'satisfaction_recommendations' => $this->getSatisfactionRecommendations($multiDimensionalScoring, $rootCauseAnalysis),
            'model_version' => '1.0',
            'calculated_at' => now()
        ];

        // Store satisfaction metrics in fact table
        $this->storeSatisfactionMetrics($clientKey, $satisfactionAnalysis);

        return $satisfactionAnalysis;
    }

    /**
     * Get customers with low satisfaction scores (at-risk)
     */
    public function getLowSatisfactionCustomers(int $limit = 50): Collection
    {
        $lowSatisfactionCustomers = FactCustomerSatisfactionMetrics::where('multi_dimensional_scoring->overall_satisfaction_score', '<', 3.0)
            ->orderBy('multi_dimensional_scoring->overall_satisfaction_score', 'asc')
            ->limit($limit)
            ->with('client')
            ->get();

        return $lowSatisfactionCustomers->map(function ($customer) {
            $scoringData = json_decode($customer->multi_dimensional_scoring, true);
            $issueData = json_decode($customer->issue_categorization, true);
            return [
                'client_key' => $customer->client_key,
                'client_name' => $customer->client->client_name ?? 'Unknown',
                'overall_satisfaction_score' => $scoringData['overall_satisfaction_score'] ?? 0,
                'primary_issues' => $issueData['primary_issue_categories'] ?? [],
                'satisfaction_trend' => $customer->satisfaction_trend_analysis['trend_direction'] ?? 'unknown',
                'severity_level' => $this->getSatisfactionSeverityLevel($scoringData['overall_satisfaction_score']),
                'recommended_intervention' => $this->getSatisfactionIntervention($issueData),
                'improvement_potential' => $this->assessImprovementPotential($customer)
            ];
        });
    }

    /**
     * Get customers with high satisfaction scores (promoters)
     */
    public function getHighSatisfactionCustomers(int $limit = 50): Collection
    {
        return FactCustomerSatisfactionMetrics::where('multi_dimensional_scoring->overall_satisfaction_score', '>=', 4.0)
            ->orderBy('multi_dimensional_scoring->overall_satisfaction_score', 'desc')
            ->limit($limit)
            ->with('client')
            ->get()
            ->map(function ($customer) {
                $scoringData = json_decode($customer->multi_dimensional_scoring, true);
                return [
                    'client_key' => $customer->client_key,
                    'client_name' => $customer->client->client_name ?? 'Unknown',
                    'overall_satisfaction_score' => $scoringData['overall_satisfaction_score'] ?? 0,
                    'nps_category' => $scoringData['nps_category'] ?? 'unknown',
                    'satisfaction_factors' => $scoringData['top_satisfaction_factors'] ?? [],
                    'retention_recommendation' => $this->getRetentionRecommendation($scoringData)
                ];
            });
    }

    /**
     * Get satisfaction insights dashboard for all customers
     */
    public function getSatisfactionDashboard(): array
    {
        $allSatisfactionData = FactCustomerSatisfactionMetrics::with('client')
            ->orderBy('calculated_at', 'desc')
            ->limit(500)
            ->get();

        $satisfactionDistribution = $this->getSatisfactionDistribution($allSatisfactionData);
        $satisfactionTrends = $this->getSatisfactionTrends($allSatisfactionData);
        $issueHotspots = $this->getIssueHotspots($allSatisfactionData);
        $satisfactionBenchmarks = $this->getSatisfactionBenchmarks();

        return [
            'overall_satisfaction_overview' => [
                'total_customers_analyzed' => $allSatisfactionData->count(),
                'average_satisfaction_score' => $this->calculateOverallAverageSatisfaction($allSatisfactionData),
                'satisfaction_distribution' => $satisfactionDistribution,
                'nps_score' => $this->calculateOverallNPS($allSatisfactionData)
            ],
            'satisfaction_trends' => $satisfactionTrends,
            'issue_analytics' => [
                'top_issue_categories' => $this->getTopIssueCategories($allSatisfactionData),
                'issue_resolution_times' => $this->getIssueResolutionTimes($allSatisfactionData),
                'issue_patterns' => $this->getIssuePatterns($allSatisfactionData)
            ],
            'satisfaction_benchmarks' => $satisfactionBenchmarks,
            'improvement_priorities' => $this->getImprovementPriorities($allSatisfactionData),
            'dashboard_updated_at' => now()
        ];
    }

    /**
     * Batch update all customer satisfaction metrics
     */
    public function batchUpdateAllSatisfactionMetrics(): array
    {
        $updated = 0;
        $errors = [];

        try {
            $clientKeys = DB::table('dimension_clients')
                ->where('is_active', true)
                ->pluck('client_key');

            foreach ($clientKeys as $clientKey) {
                try {
                    $this->calculateCustomerSatisfactionMetrics($clientKey, 90);
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
     * Get satisfaction forecasting and prediction
     */
    public function getSatisfactionForecasting(int $forecastDays = 30): array
    {
        $allCustomers = FactCustomerSatisfactionMetrics::with('client')->get();
        
        $forecastData = $this->generateSatisfactionForecast($allCustomers, $forecastDays);

        return [
            'forecast_period_days' => $forecastDays,
            'predicted_satisfaction_trends' => $forecastData['trends'],
            'satisfaction_risk_factors' => $forecastData['risks'],
            'improvement_opportunities' => $forecastData['opportunities'],
            'forecast_confidence' => $forecastData['confidence'],
            'key_assumptions' => $forecastData['assumptions'],
            'generated_at' => now()
        ];
    }

    private function collectSatisfactionData(int $clientKey, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        $startDateKey = $startDate->format('Ymd');

        // Get support tickets for satisfaction analysis
        $supportTickets = Support::where('user_id', $clientKey)
            ->where('date', '>=', $startDate)
            ->with(['supportChats', 'department'])
            ->get();

        // Get sentiment data
        $sentimentData = FactCustomerSentiment::where('client_key', $clientKey)
            ->where('sentiment_date_key', '>=', $startDateKey)
            ->get();

        // Get shipment data for service satisfaction
        $shipmentData = FactShipment::where('client_key', $clientKey)
            ->where('pickup_date_key', '>=', $startDateKey)
            ->get();

        // Get churn risk for satisfaction context
        $churnData = FactCustomerChurnMetrics::where('client_key', $clientKey)
            ->orderBy('churn_date_key', 'desc')
            ->first();

        return [
            'support_tickets' => $supportTickets,
            'sentiment_data' => $sentimentData,
            'shipment_data' => $shipmentData,
            'churn_data' => $churnData,
            'analysis_period' => $days,
            'data_completeness' => $this->assessSatisfactionDataCompleteness($supportTickets, $sentimentData, $shipmentData)
        ];
    }

    private function calculateMultiDimensionalSatisfactionScoring(array $satisfactionData): array
    {
        $supportTickets = $satisfactionData['support_tickets'];
        $sentimentData = $satisfactionData['sentiment_data'];
        $shipmentData = $satisfactionData['shipment_data'];

        // Support satisfaction component
        $supportSatisfaction = $this->calculateSupportSatisfaction($supportTickets);
        
        // Service satisfaction component
        $serviceSatisfaction = $this->calculateServiceSatisfaction($shipmentData);
        
        // Communication satisfaction component
        $communicationSatisfaction = $this->calculateCommunicationSatisfaction($sentimentData, $supportTickets);
        
        // Value satisfaction component
        $valueSatisfaction = $this->calculateValueSatisfaction($shipmentData, $sentimentData);
        
        // Overall satisfaction score (weighted average)
        $overallSatisfaction = (
            ($supportSatisfaction * 0.3) +
            ($serviceSatisfaction * 0.25) +
            ($communicationSatisfaction * 0.25) +
            ($valueSatisfaction * 0.2)
        );

        // NPS calculation
        $npsScore = $this->calculateNPSScore($overallSatisfaction);
        $npsCategory = $this->categorizeNPS($npsScore);

        // Top satisfaction factors
        $topSatisfactionFactors = $this->getTopSatisfactionFactors([
            'support' => $supportSatisfaction,
            'service' => $serviceSatisfaction,
            'communication' => $communicationSatisfaction,
            'value' => $valueSatisfaction
        ]);

        return [
            'overall_satisfaction_score' => round($overallSatisfaction, 2),
            'support_satisfaction' => round($supportSatisfaction, 2),
            'service_satisfaction' => round($serviceSatisfaction, 2),
            'communication_satisfaction' => round($communicationSatisfaction, 2),
            'value_satisfaction' => round($valueSatisfaction, 2),
            'nps_score' => round($npsScore, 1),
            'nps_category' => $npsCategory,
            'top_satisfaction_factors' => $topSatisfactionFactors,
            'satisfaction_consistency' => $this->calculateSatisfactionConsistency($satisfactionData),
            'satisfaction_evolution' => $this->trackSatisfactionEvolution($satisfactionData)
        ];
    }

    private function performIssueCategorization(array $satisfactionData): array
    {
        $supportTickets = $satisfactionData['support_tickets'];
        $sentimentData = $satisfactionData['sentiment_data'];

        if ($supportTickets->isEmpty() && $sentimentData->isEmpty()) {
            return [
                'primary_issue_categories' => [],
                'issue_frequency_distribution' => [],
                'severity_distribution' => [],
                'resolution_impact' => [],
                'issue_trends' => []
            ];
        }

        // Categorize support issues
        $issueCategories = $this->categorizeSupportIssues($supportTickets);
        
        // Analyze issue severity
        $severityDistribution = $this->analyzeIssueSeverity($supportTickets);
        
        // Calculate resolution impact
        $resolutionImpact = $this->calculateResolutionImpact($supportTickets);
        
        // Identify issue trends
        $issueTrends = $this->identifyIssueTrends($supportTickets, $sentimentData);

        return [
            'primary_issue_categories' => $issueCategories['primary'],
            'issue_frequency_distribution' => $issueCategories['frequency'],
            'severity_distribution' => $severityDistribution,
            'resolution_impact' => $resolutionImpact,
            'issue_trends' => $issueTrends,
            'categorization_confidence' => $this->calculateCategorizationConfidence($supportTickets, $sentimentData)
        ];
    }

    private function analyzeSatisfactionTrends(array $satisfactionData, int $analysisPeriod): array
    {
        $supportTickets = $satisfactionData['support_tickets'];
        $sentimentData = $satisfactionData['sentiment_data'];

        if ($supportTickets->isEmpty() && $sentimentData->isEmpty()) {
            return [
                'trend_direction' => 'no_data',
                'trend_strength' => 0,
                'trend_confidence' => 0,
                'satisfaction_volatility' => 0,
                'seasonal_patterns' => [],
                'predictive_indicators' => []
            ];
        }

        // Calculate satisfaction trends over time
        $satisfactionEvolution = $this->trackSatisfactionEvolution($satisfactionData);
        
        // Determine trend direction
        $trendDirection = $this->determineSatisfactionTrendDirection($satisfactionEvolution);
        
        // Calculate trend strength
        $trendStrength = $this->calculateTrendStrength($satisfactionEvolution);
        
        // Assess volatility
        $satisfactionVolatility = $this->calculateSatisfactionVolatility($satisfactionData);
        
        // Identify seasonal patterns
        $seasonalPatterns = $this->identifySatisfactionSeasonalPatterns($supportTickets, $sentimentData);

        return [
            'trend_direction' => $trendDirection,
            'trend_strength' => round($trendStrength, 4),
            'trend_confidence' => $this->calculateTrendConfidence($satisfactionEvolution),
            'satisfaction_volatility' => round($satisfactionVolatility, 4),
            'seasonal_patterns' => $seasonalPatterns,
            'satisfaction_evolution' => $satisfactionEvolution,
            'predictive_indicators' => $this->generateSatisfactionPredictiveIndicators($trendDirection, $satisfactionVolatility)
        ];
    }

    private function performRootCauseAnalysis(array $satisfactionData, array $multiDimensionalScoring): array
    {
        $supportTickets = $satisfactionData['support_tickets'];
        $shipmentData = $satisfactionData['shipment_data'];

        $rootCauses = [];
        $satisfactionScore = $multiDimensionalScoring['overall_satisfaction_score'];

        // Analyze low satisfaction root causes
        if ($satisfactionScore < 3.0) {
            // Support-related root causes
            if ($multiDimensionalScoring['support_satisfaction'] < 3.0) {
                $rootCauses = array_merge($rootCauses, $this->analyzeSupportRootCauses($supportTickets));
            }

            // Service-related root causes
            if ($multiDimensionalScoring['service_satisfaction'] < 3.0) {
                $rootCauses = array_merge($rootCauses, $this->analyzeServiceRootCauses($shipmentData));
            }

            // Communication-related root causes
            if ($multiDimensionalScoring['communication_satisfaction'] < 3.0) {
                $rootCauses = array_merge($rootCauses, $this->analyzeCommunicationRootCauses($supportTickets, $shipmentData));
            }
        }

        // Prioritize root causes by impact
        $prioritizedRootCauses = $this->prioritizeRootCauses($rootCauses);

        return [
            'identified_root_causes' => $prioritizedRootCauses,
            'root_cause_impact_assessment' => $this->assessRootCauseImpact($prioritizedRootCauses),
            'intervention_recommendations' => $this->getRootCauseInterventionRecommendations($prioritizedRootCauses),
            'monitoring_requirements' => $this->getRootCauseMonitoringRequirements($prioritizedRootCauses)
        ];
    }

    private function identifyImprovementOpportunities(array $satisfactionData, array $issueCategorization): array
    {
        $opportunities = [];
        $supportTickets = $satisfactionData['support_tickets'];
        $shipmentData = $satisfactionData['shipment_data'];

        // High-impact improvement opportunities
        $highImpactOpportunities = $this->identifyHighImpactOpportunities($supportTickets, $shipmentData);
        $opportunities = array_merge($opportunities, $highImpactOpportunities);

        // Quick wins
        $quickWins = $this->identifyQuickWins($supportTickets);
        $opportunities = array_merge($opportunities, $quickWins);

        // Long-term strategic improvements
        $strategicOpportunities = $this->identifyStrategicOpportunities($satisfactionData, $issueCategorization);
        $opportunities = array_merge($opportunities, $strategicOpportunities);

        return [
            'immediate_opportunities' => $this->categorizeOpportunitiesByUrgency($opportunities, 'immediate'),
            'short_term_opportunities' => $this->categorizeOpportunitiesByUrgency($opportunities, 'short_term'),
            'long_term_opportunities' => $this->categorizeOpportunitiesByUrgency($opportunities, 'long_term'),
            'opportunity_impact_assessment' => $this->assessOpportunityImpact($opportunities),
            'implementation_difficulty' => $this->assessImplementationDifficulty($opportunities),
            'roi_projections' => $this->calculateOpportunityROI($opportunities)
        ];
    }

    private function calculateSatisfactionHealthScore(array $multiDimensionalScoring, array $satisfactionTrendAnalysis): float
    {
        $satisfactionScore = $multiDimensionalScoring['overall_satisfaction_score'];
        $trendDirection = $satisfactionTrendAnalysis['trend_direction'];
        $trendStrength = $satisfactionTrendAnalysis['trend_strength'];
        $volatility = $satisfactionTrendAnalysis['satisfaction_volatility'];

        // Convert satisfaction score to 0-1 scale
        $normalizedSatisfaction = $satisfactionScore / 5.0;
        
        // Trend factor
        $trendFactor = match($trendDirection) {
            'improving' => 1.2,
            'stable' => 1.0,
            'declining' => 0.8,
            default => 0.9
        };

        // Volatility penalty
        $volatilityPenalty = max(0.5, 1.0 - ($volatility * 0.5));

        $healthScore = $normalizedSatisfaction * $trendFactor * $volatilityPenalty;
        
        return round(min(1.0, $healthScore), 4);
    }

    // Helper methods for satisfaction calculations
    private function createDefaultSatisfactionMetrics(int $clientKey): array
    {
        return [
            'client_key' => $clientKey,
            'analysis_period_days' => 0,
            'multi_dimensional_scoring' => ['overall_satisfaction_score' => 0],
            'issue_categorization' => ['primary_issue_categories' => []],
            'satisfaction_trend_analysis' => ['trend_direction' => 'no_data'],
            'root_cause_analysis' => ['identified_root_causes' => []],
            'improvement_opportunities' => [],
            'satisfaction_benchmarking' => ['benchmark_position' => 'no_data'],
            'predictive_satisfaction' => ['satisfaction_forecast' => []],
            'satisfaction_health_score' => 0,
            'satisfaction_insights' => ['insufficient_data'],
            'satisfaction_recommendations' => ['data_collection_needed'],
            'model_version' => '1.0',
            'calculated_at' => now()
        ];
    }

    private function storeSatisfactionMetrics(int $clientKey, array $satisfactionAnalysis): void
    {
        FactCustomerSatisfactionMetrics::updateOrCreate(
            ['client_key' => $clientKey],
            array_merge($satisfactionAnalysis, [
                'satisfaction_key' => $this->generateSatisfactionKey($clientKey),
                'satisfaction_date_key' => now()->format('Ymd')
            ])
        );
    }

    private function generateSatisfactionKey(int $clientKey): string
    {
        return $clientKey . '_' . now()->format('Ymd');
    }

    // Additional helper methods with placeholder implementations
    private function assessSatisfactionDataCompleteness($tickets, $sentiment, $shipments): float { return 0.8; }
    private function calculateSupportSatisfaction($tickets): float { return 3.5; }
    private function calculateServiceSatisfaction($shipments): float { return 4.0; }
    private function calculateCommunicationSatisfaction($sentiment, $tickets): float { return 3.8; }
    private function calculateValueSatisfaction($shipments, $sentiment): float { return 3.7; }
    private function calculateNPSScore($overallSatisfaction): float { return ($overallSatisfaction - 3) * 25; }
    private function categorizeNPS($npsScore): string { return $npsScore > 50 ? 'promoter' : ($npsScore > 0 ? 'passive' : 'detractor'); }
    private function getTopSatisfactionFactors($factors): array { return array_keys($factors, max($factors)); }
    private function calculateSatisfactionConsistency($data): float { return 0.7; }
    private function trackSatisfactionEvolution($data): array { return []; }
    private function categorizeSupportIssues($tickets): array { return ['primary' => ['support_response'], 'frequency' => []]; }
    private function analyzeIssueSeverity($tickets): array { return []; }
    private function calculateResolutionImpact($tickets): array { return []; }
    private function identifyIssueTrends($tickets, $sentiment): array { return []; }
    private function calculateCategorizationConfidence($tickets, $sentiment): float { return 0.8; }
    private function determineSatisfactionTrendDirection($evolution): string { return 'stable'; }
    private function calculateTrendStrength($evolution): float { return 0.5; }
    private function calculateTrendConfidence($evolution): float { return 0.7; }
    private function calculateSatisfactionVolatility($data): float { return 0.3; }
    private function identifySatisfactionSeasonalPatterns($tickets, $sentiment): array { return []; }
    private function generateSatisfactionPredictiveIndicators($direction, $volatility): array { return []; }
    private function analyzeSupportRootCauses($tickets): array { return []; }
    private function analyzeServiceRootCauses($shipments): array { return []; }
    private function analyzeCommunicationRootCauses($tickets, $shipments): array { return []; }
    private function prioritizeRootCauses($causes): array { return $causes; }
    private function assessRootCauseImpact($causes): array { return []; }
    private function getRootCauseInterventionRecommendations($causes): array { return []; }
    private function getRootCauseMonitoringRequirements($causes): array { return []; }
    private function identifyHighImpactOpportunities($tickets, $shipments): array { return []; }
    private function identifyQuickWins($tickets): array { return []; }
    private function identifyStrategicOpportunities($data, $categorization): array { return []; }
    private function categorizeOpportunitiesByUrgency($opportunities, $urgency): array { return []; }
    private function assessOpportunityImpact($opportunities): array { return []; }
    private function assessImplementationDifficulty($opportunities): array { return []; }
    private function calculateOpportunityROI($opportunities): array { return []; }
    private function performSatisfactionBenchmarking($scoring): array { return []; }
    private function calculatePredictiveSatisfaction($data): array { return []; }
    private function getSatisfactionSeverityLevel($score): string { return $score < 2.0 ? 'critical' : ($score < 3.0 ? 'high' : 'moderate'); }
    private function getSatisfactionIntervention($issueData): string { return 'immediate_followup'; }
    private function assessImprovementPotential($customer): float { return 0.6; }
    private function getRetentionRecommendation($scoring): array { return ['maintain_service_quality']; }
    private function getSatisfactionDistribution($data): array { return []; }
    private function getSatisfactionTrends($data): array { return []; }
    private function getIssueHotspots($data): array { return []; }
    private function getSatisfactionBenchmarks(): array { return []; }
    private function getTopIssueCategories($data): array { return []; }
    private function getIssueResolutionTimes($data): array { return []; }
    private function getIssuePatterns($data): array { return []; }
    private function getImprovementPriorities($data): array { return []; }
    private function calculateOverallAverageSatisfaction($data): float { return 3.5; }
    private function calculateOverallNPS($data): float { return 25.0; }
    private function generateSatisfactionForecast($data, $days): array { return []; }
}
