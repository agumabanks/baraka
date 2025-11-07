<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

/**
 * Promotion Effectiveness Metric Model
 * 
 * Tracks ROI, performance metrics, and effectiveness data for promotional campaigns
 */
class PromotionEffectivenessMetric extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'promotional_campaign_id',
        'metric_date',
        'roi_percentage',
        'revenue_impact',
        'cost_impact',
        'conversion_rate',
        'customer_engagement_score',
        'usage_count',
        'unique_customers',
        'retention_rate',
        'acquisition_cost',
        'lifetime_value_impact',
        'competitive_position',
        'market_share_impact',
        'data_source',
        'measurement_period',
        'confidence_level'
    ];

    protected $casts = [
        'metric_date' => 'date',
        'roi_percentage' => 'decimal:2',
        'revenue_impact' => 'decimal:2',
        'cost_impact' => 'decimal:2',
        'conversion_rate' => 'decimal:4',
        'customer_engagement_score' => 'decimal:2',
        'retention_rate' => 'decimal:4',
        'acquisition_cost' => 'decimal:2',
        'lifetime_value_impact' => 'decimal:2',
        'competitive_position' => 'decimal:2',
        'market_share_impact' => 'decimal:4',
        'confidence_level' => 'decimal:2',
        'usage_count' => 'integer',
        'unique_customers' => 'integer',
        'measurement_period' => 'integer'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('PromotionEffectivenessMetric')
            ->logOnly(['metric_date', 'roi_percentage', 'revenue_impact', 'conversion_rate'])
            ->setDescriptionForEvent(fn (string $eventName) => "Promotion effectiveness metric {$eventName}");
    }

    // Relationships
    public function promotionalCampaign(): BelongsTo
    {
        return $this->belongsTo(PromotionalCampaign::class);
    }

    // Scopes
    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('metric_date', '>=', now()->subDays($days));
    }

    public function scopeHighROI($query, float $minROI = 100)
    {
        return $query->where('roi_percentage', '>=', $minROI);
    }

    public function scopeByPerformance($query, string $performance = 'high')
    {
        return match($performance) {
            'high' => $query->where('roi_percentage', '>=', 150),
            'medium' => $query->whereBetween('roi_percentage', [75, 150]),
            'low' => $query->where('roi_percentage', '<', 75),
            default => $query
        };
    }

    public function scopeByConversionRate($query, float $minRate = 0.1)
    {
        return $query->where('conversion_rate', '>=', $minRate);
    }

    // Business Logic
    public function getPerformanceRating(): string
    {
        return match(true) {
            $this->roi_percentage >= 200 => 'excellent',
            $this->roi_percentage >= 150 => 'very_good',
            $this->roi_percentage >= 100 => 'good',
            $this->roi_percentage >= 75 => 'acceptable',
            $this->roi_percentage >= 50 => 'poor',
            default => 'critical'
        };
    }

    public function getROIStatus(): string
    {
        return match(true) {
            $this->roi_percentage > 200 => 'exceptional_performance',
            $this->roi_percentage > 150 => 'strong_performance',
            $this->roi_percentage > 100 => 'profitable',
            $this->roi_percentage >= 75 => 'break_even',
            $this->roi_percentage >= 50 => 'marginal',
            default => 'loss_making'
        };
    }

    public function getBusinessImpact(): array
    {
        return [
            'financial_impact' => [
                'revenue_generated' => $this->revenue_impact,
                'cost_invested' => $this->cost_impact,
                'net_profit' => $this->revenue_impact - $this->cost_impact,
                'roi' => $this->roi_percentage
            ],
            'customer_impact' => [
                'new_customers' => $this->unique_customers,
                'engagement_level' => $this->customer_engagement_score,
                'retention_impact' => $this->retention_rate,
                'acquisition_cost' => $this->acquisition_cost
            ],
            'strategic_impact' => [
                'market_position' => $this->competitive_position,
                'market_share_change' => $this->market_share_impact,
                'competitive_advantage' => $this->calculateCompetitiveAdvantage(),
                'strategic_value' => $this->calculateStrategicValue()
            ]
        ];
    }

    public function getForecastMetrics(): array
    {
        // Calculate trend-based forecasts
        $recentMetrics = self::where('promotional_campaign_id', $this->promotional_campaign_id)
            ->where('metric_date', '<=', $this->metric_date)
            ->orderBy('metric_date', 'desc')
            ->limit(7)
            ->get();

        if ($recentMetrics->count() < 2) {
            return [
                'forecast_roi' => $this->roi_percentage,
                'trend' => 'insufficient_data',
                'confidence' => 0
            ];
        }

        $roiTrend = $this->calculateROITrend($recentMetrics);
        $forecastROI = $this->forecastROI($roiTrend);

        return [
            'forecast_roi' => $forecastROI,
            'trend_direction' => $roiTrend['direction'],
            'trend_strength' => $roiTrend['strength'],
            'confidence' => min(95, $recentMetrics->count() * 10),
            'recommendation' => $this->generateForecastRecommendation($roiTrend, $forecastROI)
        ];
    }

    public function getComparisonMetrics(): array
    {
        $campaign = $this->promotionalCampaign;
        
        // Get similar campaigns for comparison
        $similarCampaigns = self::where('promotional_campaign_id', '!=', $this->promotional_campaign_id)
            ->whereHas('promotionalCampaign', function ($query) use ($campaign) {
                $query->where('campaign_type', $campaign->campaign_type)
                      ->whereBetween('value', [$campaign->value * 0.8, $campaign->value * 1.2]);
            })
            ->whereBetween('metric_date', [
                $this->metric_date->copy()->subDays(30),
                $this->metric_date->copy()->addDays(30)
            ])
            ->get();

        if ($similarCampaigns->isEmpty()) {
            return [
                'vs_industry_average' => null,
                'vs_competitors' => null,
                'market_benchmark' => null
            ];
        }

        return [
            'vs_industry_average' => [
                'roi' => $this->roi_percentage - $similarCampaigns->avg('roi_percentage'),
                'conversion_rate' => $this->conversion_rate - $similarCampaigns->avg('conversion_rate'),
                'engagement' => $this->customer_engagement_score - $similarCampaigns->avg('customer_engagement_score')
            ],
            'vs_competitors' => [
                'position' => $this->calculateCompetitivePosition($similarCampaigns),
                'advantage' => $this->calculateCompetitiveAdvantage()
            ],
            'market_benchmark' => [
                'percentile_rank' => $this->calculatePercentileRank($similarCampaigns),
                'market_standing' => $this->getMarketStanding()
            ]
        ];
    }

    // Static methods for analytics
    public static function getCampaignDashboardData(int $campaignId, string $timeframe = '30d'): array
    {
        $days = match($timeframe) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };

        $metrics = self::where('promotional_campaign_id', $campaignId)
            ->where('metric_date', '>=', now()->subDays($days))
            ->orderBy('metric_date')
            ->get();

        if ($metrics->isEmpty()) {
            return [
                'performance_trend' => [],
                'summary_metrics' => null,
                'insights' => ['No data available for the selected timeframe']
            ];
        }

        return [
            'performance_trend' => $metrics->map(function ($metric) {
                return [
                    'date' => $metric->metric_date->toDateString(),
                    'roi' => $metric->roi_percentage,
                    'conversion_rate' => $metric->conversion_rate,
                    'engagement' => $metric->customer_engagement_score,
                    'revenue' => $metric->revenue_impact
                ];
            }),
            'summary_metrics' => [
                'average_roi' => $metrics->avg('roi_percentage'),
                'peak_roi' => $metrics->max('roi_percentage'),
                'total_revenue' => $metrics->sum('revenue_impact'),
                'average_conversion' => $metrics->avg('conversion_rate'),
                'total_customers' => $metrics->sum('unique_customers'),
                'performance_rating' => $metrics->last()->getPerformanceRating()
            ],
            'insights' => self::generateDashboardInsights($metrics)
        ];
    }

    // Private helper methods
    private function calculateCompetitiveAdvantage(): string
    {
        $roi = $this->roi_percentage;
        $conversion = $this->conversion_rate;
        $engagement = $this->customer_engagement_score;
        
        $score = ($roi / 100) * 0.4 + ($conversion * 1000) * 0.3 + $engagement * 0.3;
        
        return match(true) {
            $score >= 150 => 'significant_advantage',
            $score >= 120 => 'moderate_advantage',
            $score >= 100 => 'slight_advantage',
            $score >= 80 => 'slight_disadvantage',
            $score >= 60 => 'moderate_disadvantage',
            default => 'significant_disadvantage'
        };
    }

    private function calculateStrategicValue(): string
    {
        $roi = $this->roi_percentage;
        $retention = $this->retention_rate;
        $lifetimeImpact = $this->lifetime_value_impact;
        
        return match(true) {
            $roi > 150 && $retention > 0.7 && $lifetimeImpact > 1000 => 'high_strategic_value',
            $roi > 100 && $retention > 0.5 && $lifetimeImpact > 500 => 'moderate_strategic_value',
            $roi > 75 && $retention > 0.3 => 'low_strategic_value',
            default => 'minimal_strategic_value'
        };
    }

    private function calculateROITrend($recentMetrics): array
    {
        $roiValues = $recentMetrics->pluck('roi_percentage')->toArray();
        
        if (count($roiValues) < 2) {
            return ['direction' => 'stable', 'strength' => 0];
        }
        
        $firstHalf = array_slice($roiValues, 0, floor(count($roiValues) / 2));
        $secondHalf = array_slice($roiValues, floor(count($roiValues) / 2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $change = $secondAvg - $firstAvg;
        $strength = abs($change) / $firstAvg * 100;
        
        return [
            'direction' => $change > 5 ? 'improving' : ($change < -5 ? 'declining' : 'stable'),
            'strength' => $strength,
            'change' => $change
        ];
    }

    private function forecastROI(array $trend): float
    {
        $currentROI = $this->roi_percentage;
        $trendChange = $trend['change'];
        
        // Simple linear forecast
        return max(0, $currentROI + $trendChange);
    }

    private function generateForecastRecommendation(array $trend, float $forecastROI): string
    {
        return match($trend['direction']) {
            'improving' => 'Strong positive trend. Consider scaling the campaign.',
            'declining' => 'Negative trend detected. Review and optimize campaign parameters.',
            'stable' => 'Stable performance. Monitor for opportunities to improve.',
            default => 'Insufficient data for trend analysis.'
        };
    }

    private function calculateCompetitivePosition($similarCampaigns): string
    {
        $percentile = $this->calculatePercentileRank($similarCampaigns);
        
        return match(true) {
            $percentile >= 90 => 'market_leader',
            $percentile >= 75 => 'above_average',
            $percentile >= 50 => 'average',
            $percentile >= 25 => 'below_average',
            default => 'market_laggard'
        };
    }

    private function calculatePercentileRank($similarCampaigns): float
    {
        $allROI = $similarCampaigns->pluck('roi_percentage')->push($this->roi_percentage)->sort()->values();
        $position = $allROI->search($this->roi_percentage) + 1;
        
        return ($position / $allROI->count()) * 100;
    }

    private function getMarketStanding(): string
    {
        $roi = $this->roi_percentage;
        $conversion = $this->conversion_rate * 100;
        
        return match(true) {
            $roi > 150 && $conversion > 15 => 'leader',
            $roi > 120 && $conversion > 10 => 'challenger',
            $roi > 90 && $conversion > 5 => 'follower',
            default => 'niche_player'
        };
    }

    private static function generateDashboardInsights($metrics): array
    {
        $insights = [];
        
        $avgROI = $metrics->avg('roi_percentage');
        $peakROI = $metrics->max('roi_percentage');
        $latestROI = $metrics->last()->roi_percentage;
        
        if ($avgROI > 150) {
            $insights[] = "Strong performance with an average ROI of {$avgROI}%";
        }
        
        if ($latestROI > $avgROI * 1.2) {
            $insights[] = "Recent performance is significantly above average";
        }
        
        if ($metrics->last()->conversion_rate > 0.15) {
            $insights[] = "High conversion rate indicates effective targeting";
        }
        
        if (empty($insights)) {
            $insights[] = "Performance within expected parameters";
        }
        
        return $insights;
    }
}