<?php

namespace App\Services\CustomerIntelligence;

use App\Models\ETL\FactCustomerChurnMetrics;
use App\Models\ETL\FactShipment;
use App\Models\ETL\DimensionChurnFactors;
use App\Models\Backend\Support;
use App\Models\ETL\FactFinancialTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChurnPredictionService
{
    /**
     * Calculate churn probability for a specific customer
     */
    public function calculateChurnProbability(int $clientKey): array
    {
        $churnMetrics = $this->getCustomerChurnMetrics($clientKey);
        
        if (empty($churnMetrics)) {
            return $this->createDefaultChurnMetrics($clientKey);
        }

        // Apply machine learning model
        $churnProbability = $this->applyChurnModel($churnMetrics);
        $riskScore = $this->calculateRiskScore($churnMetrics, $churnProbability);
        $retentionScore = $this->calculateRetentionScore($churnMetrics, $churnProbability);

        return [
            'client_key' => $clientKey,
            'churn_probability' => round($churnProbability, 4),
            'risk_score' => round($riskScore, 4),
            'retention_score' => round($retentionScore, 4),
            'churn_indicators' => $this->identifyChurnIndicators($churnMetrics),
            'primary_churn_factors' => $this->getPrimaryChurnFactors($churnMetrics),
            'secondary_churn_factors' => $this->getSecondaryChurnFactors($churnMetrics),
            'predicted_churn_date' => $this->predictChurnDate($churnProbability, $churnMetrics),
            'recommended_actions' => $this->getRecommendedActions($churnProbability, $churnMetrics),
            'confidence_level' => $this->calculateConfidenceLevel($churnMetrics),
            'model_version' => '1.0',
            'calculated_at' => now()
        ];
    }

    /**
     * Get high-risk customers for immediate attention
     */
    public function getHighRiskCustomers(int $limit = 50): Collection
    {
        $highRiskCustomers = FactCustomerChurnMetrics::where('churn_probability', '>=', 0.7)
            ->orderBy('churn_probability', 'desc')
            ->limit($limit)
            ->with('client')
            ->get();

        return $highRiskCustomers->map(function ($customer) {
            return [
                'client_key' => $customer->client_key,
                'client_name' => $customer->client->client_name ?? 'Unknown',
                'churn_probability' => $customer->churn_probability,
                'risk_level' => $customer->getRiskLevel(),
                'primary_factors' => $customer->getTopChurnFactors(),
                'recommended_actions' => $customer->recommended_actions,
                'days_since_last_shipment' => $customer->days_since_last_shipment,
                'predicted_churn_date' => $customer->predicted_churn_date
            ];
        });
    }

    /**
     * Batch update churn predictions for all customers
     */
    public function updateAllChurnPredictions(): array
    {
        $updated = 0;
        $errors = [];

        try {
            $clientKeys = DB::table('dimension_clients')
                ->where('is_active', true)
                ->pluck('client_key');

            foreach ($clientKeys as $clientKey) {
                try {
                    $this->updateCustomerChurnPrediction($clientKey);
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
     * Get churn trend analysis over time
     */
    public function getChurnTrendAnalysis(int $days = 90): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $trends = DB::table('fact_customer_churn_metrics as churn')
            ->join('dimension_dates as dates', 'churn.churn_date_key', '=', 'dates.date_key')
            ->where('dates.date_value', '>=', $startDate)
            ->select(
                'dates.date_value',
                DB::raw('AVG(churn.churn_probability) as avg_churn_probability'),
                DB::raw('COUNT(*) as customer_count'),
                DB::raw('SUM(CASE WHEN churn.churn_probability >= 0.7 THEN 1 ELSE 0 END) as high_risk_count')
            )
            ->groupBy('dates.date_value')
            ->orderBy('dates.date_value')
            ->get();

        return [
            'trend_data' => $trends,
            'summary' => [
                'overall_trend' => $this->calculateOverallTrend($trends),
                'high_risk_percentage' => $trends->avg(function ($item) {
                    return $item->customer_count > 0 ? ($item->high_risk_count / $item->customer_count) * 100 : 0;
                }),
                'average_churn_probability' => $trends->avg('avg_churn_probability')
            ]
        ];
    }

    /**
     * Get churn factor analysis
     */
    public function getChurnFactorAnalysis(): array
    {
        $factorAnalysis = DB::table('fact_customer_churn_metrics as churn')
            ->join('dimension_churn_factors as factors', 'churn.primary_churn_factors', 'LIKE', '%' . DB::raw('factors.factor_key') . '%')
            ->select(
                'factors.factor_name',
                'factors.factor_category',
                'factors.weight_in_model',
                DB::raw('COUNT(*) as frequency'),
                DB::raw('AVG(churn.churn_probability) as avg_impact')
            )
            ->whereIn('factors.factor_key', function ($query) {
                $query->select(DB::raw('primary_churn_factors->$[0]'))
                    ->from('fact_customer_churn_metrics')
                    ->whereNotNull('primary_churn_factors')
                    ->limit(1000);
            })
            ->groupBy('factors.factor_key', 'factors.factor_name', 'factors.factor_category', 'factors.weight_in_model')
            ->orderBy('frequency', 'desc')
            ->get();

        return [
            'factor_analysis' => $factorAnalysis,
            'top_factors' => $factorAnalysis->take(10)->values(),
            'factor_categories' => $factorAnalysis->groupBy('factor_category')
        ];
    }

    private function getCustomerChurnMetrics(int $clientKey): array
    {
        // Get recent shipment data
        $shipmentMetrics = $this->getShipmentMetrics($clientKey);
        
        // Get support ticket data
        $supportMetrics = $this->getSupportMetrics($clientKey);
        
        // Get financial data
        $financialMetrics = $this->getFinancialMetrics($clientKey);

        return array_merge($shipmentMetrics, $supportMetrics, $financialMetrics);
    }

    private function getShipmentMetrics(int $clientKey): array
    {
        $now = Carbon::now();
        $days90 = $now->copy()->subDays(90);
        $days30 = $now->copy()->subDays(30);

        $shipments90Days = FactShipment::where('client_key', $clientKey)
            ->where('pickup_date_key', '>=', $days90->format('Ymd'))
            ->count();

        $shipments30Days = FactShipment::where('client_key', $clientKey)
            ->where('pickup_date_key', '>=', $days30->format('Ymd'))
            ->count();

        $lastShipmentDate = FactShipment::where('client_key', $clientKey)
            ->orderBy('pickup_date_key', 'desc')
            ->first();

        $daysSinceLastShipment = $lastShipmentDate 
            ? $now->diffInDays(Carbon::createFromFormat('Ymd', $lastShipmentDate->pickup_date_key))
            : 999;

        return [
            'total_shipments_90_days' => $shipments90Days,
            'total_shipments_30_days' => $shipments30Days,
            'days_since_last_shipment' => $daysSinceLastShipment,
            'shipment_frequency_30_days' => $shipments30Days / 30.0,
            'shipment_frequency_90_days' => $shipments90Days / 90.0,
            'frequency_decline' => $shipments90Days > 0 ? ($shipments30Days / 90.0) / ($shipments90Days / 90.0) : 0
        ];
    }

    private function getSupportMetrics(int $clientKey): array
    {
        $now = Carbon::now();
        $days90 = $now->copy()->subDays(90);

        $complaintsCount = Support::whereHas('user', function ($query) use ($clientKey) {
            $query->whereHas('client', function ($q) use ($clientKey) {
                $q->where('client_key', $clientKey);
            });
        })->where('created_at', '>=', $days90)
        ->whereIn('status', ['pending', 'processing'])
        ->count();

        $negativeSentimentCount = DB::table('fact_customer_sentiment')
            ->where('client_key', $clientKey)
            ->where('sentiment_date_key', '>=', $days90->format('Ymd'))
            ->where('sentiment_score', '<', -0.1)
            ->count();

        return [
            'complaints_count_90_days' => $complaintsCount,
            'negative_sentiment_count_90_days' => $negativeSentimentCount
        ];
    }

    private function getFinancialMetrics(int $clientKey): array
    {
        $now = Carbon::now();
        $days90 = $now->copy()->subDays(90);

        $paymentDelays = FactFinancialTransaction::where('client_key', $clientKey)
            ->where('transaction_date_key', '>=', $days90->format('Ymd'))
            ->where('payment_date_key', '>', 'due_date_key')
            ->count();

        $creditUtilization = DB::table('dimension_clients')
            ->where('client_key', $clientKey)
            ->value(DB::raw('COALESCE(current_balance / NULLIF(credit_limit, 0), 0)'));

        return [
            'payment_delays_90_days' => $paymentDelays,
            'credit_utilization' => $creditUtilization ?: 0
        ];
    }

    private function applyChurnModel(array $metrics): float
    {
        // Logistic regression model for churn prediction
        $weights = [
            'frequency_decline' => 2.5,
            'days_since_last_shipment' => 0.01,
            'complaints_count_90_days' => 0.3,
            'credit_utilization' => 1.0,
            'payment_delays_90_days' => 0.2,
            'negative_sentiment_count_90_days' => 0.4
        ];

        $linearCombination = 0;
        
        // Frequency decline (strongest predictor)
        $linearCombination += $weights['frequency_decline'] * max(0, min(1, $metrics['frequency_decline']));
        
        // Days since last shipment
        $linearCombination += $weights['days_since_last_shipment'] * min($metrics['days_since_last_shipment'], 365);
        
        // Support metrics
        $linearCombination += $weights['complaints_count_90_days'] * $metrics['complaints_count_90_days'];
        $linearCombination += $weights['negative_sentiment_count_90_days'] * $metrics['negative_sentiment_count_90_days'];
        
        // Financial metrics
        $linearCombination += $weights['credit_utilization'] * $metrics['credit_utilization'];
        $linearCombination += $weights['payment_delays_90_days'] * $metrics['payment_delays_90_days'];

        // Sigmoid function to get probability between 0 and 1
        return 1 / (1 + exp(-$linearCombination));
    }

    private function calculateRiskScore(array $metrics, float $churnProbability): float
    {
        $riskFactors = 0;
        
        if ($metrics['days_since_last_shipment'] > 30) $riskFactors += 0.2;
        if ($metrics['frequency_decline'] < 0.5) $riskFactors += 0.3;
        if ($metrics['complaints_count_90_days'] > 2) $riskFactors += 0.2;
        if ($metrics['credit_utilization'] > 0.7) $riskFactors += 0.15;
        if ($metrics['payment_delays_90_days'] > 1) $riskFactors += 0.15;
        
        return min(1.0, $riskFactors + ($churnProbability * 0.5));
    }

    private function calculateRetentionScore(array $metrics, float $churnProbability): float
    {
        $retentionFactors = 0;
        
        if ($metrics['total_shipments_90_days'] > 10) $retentionFactors += 0.2;
        if ($metrics['days_since_last_shipment'] < 7) $retentionFactors += 0.3;
        if ($metrics['frequency_decline'] > 0.8) $retentionFactors += 0.2;
        if ($metrics['credit_utilization'] < 0.3) $retentionFactors += 0.15;
        if ($metrics['payment_delays_90_days'] == 0) $retentionFactors += 0.15;
        
        return max(0, $retentionFactors - ($churnProbability * 0.3));
    }

    private function identifyChurnIndicators(array $metrics): array
    {
        $indicators = [];
        
        if ($metrics['days_since_last_shipment'] > 30) {
            $indicators[] = 'Long inactivity period';
        }
        if ($metrics['frequency_decline'] < 0.5) {
            $indicators[] = 'Significant frequency decline';
        }
        if ($metrics['complaints_count_90_days'] > 2) {
            $indicators[] = 'Multiple complaints';
        }
        if ($metrics['credit_utilization'] > 0.8) {
            $indicators[] = 'High credit utilization';
        }
        if ($metrics['payment_delays_90_days'] > 2) {
            $indicators[] = 'Payment delays';
        }
        
        return $indicators;
    }

    private function getPrimaryChurnFactors(array $metrics): array
    {
        $factors = [];
        
        if ($metrics['frequency_decline'] < 0.5) {
            $factors[] = 'CF002'; // Shipment Frequency Decline
        }
        if ($metrics['days_since_last_shipment'] > 30) {
            $factors[] = 'CF001'; // Days Since Last Shipment
        }
        if ($metrics['complaints_count_90_days'] > 2) {
            $factors[] = 'CF004'; // Support Ticket Complaints
        }
        
        return $factors;
    }

    private function getSecondaryChurnFactors(array $metrics): array
    {
        $factors = [];
        
        if ($metrics['credit_utilization'] > 0.7) {
            $factors[] = 'CF003'; // Credit Utilization
        }
        if ($metrics['payment_delays_90_days'] > 1) {
            $factors[] = 'CF005'; // Payment Delays
        }
        
        return $factors;
    }

    private function predictChurnDate(float $churnProbability, array $metrics): ?string
    {
        if ($churnProbability < 0.3) {
            return null; // Low probability, no predicted churn
        }
        
        $daysToChurn = 365 - ($churnProbability * 180); // 6 months max for high risk
        
        return Carbon::now()->addDays($daysToChurn)->format('Y-m-d');
    }

    private function getRecommendedActions(float $churnProbability, array $metrics): array
    {
        $actions = [];
        
        if ($churnProbability >= 0.7) {
            $actions[] = 'Immediate personal outreach required';
            $actions[] = 'Offer special retention incentives';
            $actions[] = 'Escalate to account management team';
        } elseif ($churnProbability >= 0.5) {
            $actions[] = 'Proactive customer service contact';
            $actions[] = 'Review and improve service experience';
            $actions[] = 'Consider value-added service offers';
        } else {
            $actions[] = 'Monitor closely with automated communications';
            $actions[] = 'Engage with relevant content and updates';
        }
        
        if ($metrics['days_since_last_shipment'] > 30) {
            $actions[] = 'Send re-engagement campaign';
        }
        if ($metrics['frequency_decline'] < 0.5) {
            $actions[] = 'Investigate decline reasons and address concerns';
        }
        
        return $actions;
    }

    private function calculateConfidenceLevel(array $metrics): float
    {
        $confidence = 0.8; // Base confidence
        
        if ($metrics['total_shipments_90_days'] > 5) $confidence += 0.1;
        if ($metrics['days_since_last_shipment'] < 90) $confidence += 0.05;
        if (count($this->identifyChurnIndicators($metrics)) > 0) $confidence += 0.05;
        
        return min(1.0, $confidence);
    }

    private function createDefaultChurnMetrics(int $clientKey): array
    {
        return [
            'client_key' => $clientKey,
            'churn_probability' => 0.1,
            'risk_score' => 0.1,
            'retention_score' => 0.9,
            'days_since_last_shipment' => 999,
            'total_shipments_90_days' => 0,
            'complaints_count_90_days' => 0,
            'payment_delays_90_days' => 0,
            'credit_utilization' => 0,
            'churn_indicators' => ['No recent activity'],
            'primary_churn_factors' => ['CF001'], // Days Since Last Shipment
            'secondary_churn_factors' => [],
            'recommended_actions' => ['Re-engagement campaign required'],
            'confidence_level' => 0.5,
            'model_version' => '1.0',
            'calculated_at' => now()
        ];
    }

    private function updateCustomerChurnPrediction(int $clientKey): void
    {
        $metrics = $this->calculateChurnProbability($clientKey);
        
        FactCustomerChurnMetrics::updateOrCreate(
            ['client_key' => $clientKey],
            [
                'churn_key' => $this->generateChurnKey($clientKey),
                'churn_date_key' => now()->format('Ymd'),
                'churn_probability' => $metrics['churn_probability'],
                'risk_score' => $metrics['risk_score'],
                'retention_score' => $metrics['retention_score'],
                'days_since_last_shipment' => $metrics['days_since_last_shipment'],
                'total_shipments_90_days' => $metrics['total_shipments_90_days'] ?? 0,
                'complaints_count_90_days' => $metrics['complaints_count_90_days'] ?? 0,
                'payment_delays_90_days' => $metrics['payment_delays_90_days'] ?? 0,
                'credit_utilization' => $metrics['credit_utilization'] ?? 0,
                'churn_indicators' => $metrics['churn_indicators'],
                'primary_churn_factors' => $metrics['primary_churn_factors'],
                'secondary_churn_factors' => $metrics['secondary_churn_factors'],
                'predicted_churn_date' => $metrics['predicted_churn_date'],
                'recommended_actions' => $metrics['recommended_actions'],
                'model_version' => $metrics['model_version'],
                'confidence_level' => $metrics['confidence_level']
            ]
        );
    }

    private function generateChurnKey(int $clientKey): string
    {
        return $clientKey . '_' . now()->format('Ymd');
    }

    private function calculateOverallTrend($trends): string
    {
        if ($trends->count() < 2) {
            return 'insufficient_data';
        }

        $firstHalf = $trends->take(floor($trends->count() / 2))->avg('avg_churn_probability');
        $secondHalf = $trends->skip(floor($trends->count() / 2))->avg('avg_churn_probability');
        
        $change = ($secondHalf - $firstHalf) / $firstHalf;
        
        if ($change > 0.1) return 'increasing';
        if ($change < -0.1) return 'decreasing';
        return 'stable';
    }
}