<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrossMarginAnalysis extends Model
{
    protected $fillable = [
        'shipment_key',
        'client_key',
        'route_key',
        'period_key',
        'total_revenue',
        'total_cogs',
        'gross_profit',
        'gross_margin_percentage',
        'gross_margin_rate',
        'historical_margin_percentage',
        'forecasted_margin_percentage',
        'margin_variance',
        'margin_variance_percentage',
        'competitive_benchmark',
        'industry_benchmark',
        'margin_trend_direction',
        'margin_ranking',
        'margin_score',
        'profitability_index',
        'margin_per_shipment',
        'margin_per_mile',
        'margin_analysis_date',
        'forecast_period',
        'prediction_confidence',
        'notes'
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'total_cogs' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'gross_margin_percentage' => 'decimal:4',
        'gross_margin_rate' => 'decimal:4',
        'historical_margin_percentage' => 'decimal:4',
        'forecasted_margin_percentage' => 'decimal:4',
        'margin_variance' => 'decimal:2',
        'margin_variance_percentage' => 'decimal:4',
        'competitive_benchmark' => 'decimal:4',
        'industry_benchmark' => 'decimal:4',
        'margin_ranking' => 'integer',
        'margin_score' => 'decimal:2',
        'profitability_index' => 'decimal:4',
        'margin_per_shipment' => 'decimal:2',
        'margin_per_mile' => 'decimal:2',
        'margin_analysis_date' => 'date',
        'forecast_period' => 'integer',
        'prediction_confidence' => 'decimal:3'
    ];

    // Trend direction constants
    const TREND_UP = 'up';
    const TREND_DOWN = 'down';
    const TREND_STABLE = 'stable';
    const TREND_VOLATILE = 'volatile';

    // Margin ranking categories
    const RANKING_EXCELLENT = 'excellent';
    const RANKING_GOOD = 'good';
    const RANKING_AVERAGE = 'average';
    const RANKING_POOR = 'poor';
    const RANKING_CRITICAL = 'critical';

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\FactShipment::class, 'shipment_key', 'shipment_key');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionClient::class, 'client_key', 'client_key');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionRoute::class, 'route_key', 'route_key');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionDate::class, 'period_key', 'date_key');
    }

    // Scopes
    public function scopeByRanking($query, $ranking)
    {
        return $query->where('margin_ranking', $ranking);
    }

    public function scopeByTrend($query, $trend)
    {
        return $query->where('margin_trend_direction', $trend);
    }

    public function scopeHighMargin($query, $threshold = 30)
    {
        return $query->where('gross_margin_percentage', '>=', $threshold);
    }

    public function scopeLowMargin($query, $threshold = 10)
    {
        return $query->where('gross_margin_percentage', '<=', $threshold);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('margin_analysis_date', [$startDate, $endDate]);
    }

    public function scopeByClient($query, $clientKey)
    {
        return $query->where('client_key', $clientKey);
    }

    public function scopeByRoute($query, $routeKey)
    {
        return $query->where('route_key', $routeKey);
    }

    public function scopeAboveBenchmark($query)
    {
        return $query->where('gross_margin_percentage', '>', 'competitive_benchmark');
    }

    // Helper methods
    public function calculateGrossProfit(): float
    {
        return $this->total_revenue - $this->total_cogs;
    }

    public function calculateGrossMarginPercentage(): float
    {
        if ($this->total_revenue <= 0) {
            return 0;
        }

        return ($this->calculateGrossProfit() / $this->total_revenue) * 100;
    }

    public function calculateMarginVariance(): void
    {
        $this->gross_profit = $this->calculateGrossProfit();
        $this->gross_margin_percentage = $this->calculateGrossMarginPercentage();
        
        if ($this->historical_margin_percentage > 0) {
            $this->margin_variance = $this->gross_margin_percentage - $this->historical_margin_percentage;
            $this->margin_variance_percentage = ($this->margin_variance / $this->historical_margin_percentage) * 100;
        }
    }

    public function isProfitable(): bool
    {
        return $this->gross_profit > 0;
    }

    public function isHighMargin(): bool
    {
        return $this->gross_margin_percentage >= 30;
    }

    public function isLowMargin(): bool
    {
        return $this->gross_margin_percentage <= 10;
    }

    public function isAboveBenchmark(): bool
    {
        return $this->gross_margin_percentage > $this->competitive_benchmark;
    }

    public function getTrendDirection(): string
    {
        if ($this->margin_variance > 2) {
            return self::TREND_UP;
        } elseif ($this->margin_variance < -2) {
            return self::TREND_DOWN;
        } elseif ($this->margin_variance_percentage > 10 || $this->margin_variance_percentage < -10) {
            return self::TREND_VOLATILE;
        } else {
            return self::TREND_STABLE;
        }
    }

    public function getRankingCategory(): string
    {
        return match(true) {
            $this->gross_margin_percentage >= 40 => self::RANKING_EXCELLENT,
            $this->gross_margin_percentage >= 25 => self::RANKING_GOOD,
            $this->gross_margin_percentage >= 15 => self::RANKING_AVERAGE,
            $this->gross_margin_percentage >= 5 => self::RANKING_POOR,
            default => self::RANKING_CRITICAL
        };
    }

    public function calculateProfitabilityIndex(): float
    {
        $maxMargin = max($this->gross_margin_percentage, $this->competitive_benchmark, $this->industry_benchmark);
        
        if ($maxMargin <= 0) {
            return 0;
        }

        return ($this->gross_margin_percentage / $maxMargin) * 100;
    }

    public function calculateMarginScore(): float
    {
        $score = 0;
        
        // Base score from gross margin percentage
        $score += min($this->gross_margin_percentage, 50) * 2;
        
        // Bonus for being above benchmarks
        if ($this->isAboveBenchmark()) {
            $score += 10;
        }
        
        // Trend direction impact
        $score += match($this->getTrendDirection()) {
            self::TREND_UP => 15,
            self::TREND_STABLE => 10,
            self::TREND_VOLATILE => 5,
            self::TREND_DOWN => 0,
            default => 0
        };
        
        // Consistency bonus (lower volatility)
        $volatility = abs($this->margin_variance_percentage);
        if ($volatility < 5) {
            $score += 10;
        } elseif ($volatility < 10) {
            $score += 5;
        }
        
        return min($score, 100);
    }

    public function getMarginPerShipment(float $shipmentCount): float
    {
        return $shipmentCount > 0 ? $this->gross_profit / $shipmentCount : 0;
    }

    public function getMarginPerMile(float $totalMiles): float
    {
        return $totalMiles > 0 ? $this->gross_profit / $totalMiles : 0;
    }

    public function forecastMargin(array $historicalData, int $forecastPeriods = 12): array
    {
        $forecast = [];
        $trend = $this->calculateTrendFromHistoricalData($historicalData);
        
        for ($i = 1; $i <= $forecastPeriods; $i++) {
            $forecastedMargin = $this->gross_margin_percentage + ($trend * $i);
            $forecast[] = [
                'period' => $i,
                'forecasted_margin' => max(0, $forecastedMargin), // Margins can't be negative
                'confidence' => max(0.5, 1 - ($i * 0.05)) // Decreasing confidence over time
            ];
        }
        
        return $forecast;
    }

    private function calculateTrendFromHistoricalData(array $historicalData): float
    {
        if (count($historicalData) < 2) {
            return 0;
        }
        
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $n = count($historicalData);
        
        foreach ($historicalData as $index => $margin) {
            $x = $index + 1;
            $y = $margin;
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        return $slope;
    }
}