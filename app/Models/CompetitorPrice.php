<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class CompetitorPrice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'carrier_name',
        'origin_country',
        'destination_country',
        'service_level',
        'price',
        'currency',
        'weight_kg',
        'source_type',
        'collected_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'currency' => 'string',
        'weight_kg' => 'decimal:2',
        'collected_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('CompetitorPrice')
            ->logOnly(['carrier_name', 'origin_country', 'destination_country', 'service_level', 'price', 'currency'])
            ->setDescriptionForEvent(fn (string $eventName) => "Competitor price {$eventName}");
    }

    // Scopes
    public function scopeByCarrier($query, string $carrier)
    {
        return $query->where('carrier_name', $carrier);
    }

    public function scopeByRoute($query, string $origin, string $destination)
    {
        return $query->where('origin_country', $origin)
                    ->where('destination_country', $destination);
    }

    public function scopeByServiceLevel($query, string $serviceLevel)
    {
        return $query->where('service_level', $serviceLevel);
    }

    public function scopeBySourceType($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('collected_at', '>=', now()->subDays($days));
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('collected_at', [$startDate, $endDate]);
    }

    // Business Logic
    public function getRouteCode(): string
    {
        return "{$this->origin_country}-{$this->destination_country}";
    }

    public function getFullServiceName(): string
    {
        return "{$this->carrier_name} - {$this->service_level} ({$this->getRouteCode()})";
    }

    public function getPriceInUSD(): float
    {
        // This would need exchange rate conversion in a real implementation
        // For now, assume USD
        if ($this->currency === 'USD') {
            return $this->price;
        }
        
        // Placeholder for currency conversion
        return $this->price; // Should be converted to USD
    }

    public function isRecent(): bool
    {
        return $this->collected_at->isAfter(now()->subDays(7));
    }

    public function isOutdated(): bool
    {
        return $this->collected_at->isBefore(now()->subDays(90));
    }

    public static function getRouteAverages(string $origin, string $destination, ?string $serviceLevel = null, int $days = 30): array
    {
        $query = self::byRoute($origin, $destination)->recent($days);
        
        if ($serviceLevel) {
            $query->byServiceLevel($serviceLevel);
        }
        
        $prices = $query->pluck('price', 'carrier_name');
        
        if ($prices->isEmpty()) {
            return [];
        }
        
        $averages = [];
        $overallAverage = $prices->avg();
        
        foreach ($prices as $carrier => $price) {
            $averages[$carrier] = [
                'price' => $price,
                'percentage_from_average' => ($price / $overallAverage - 1) * 100,
                'is_above_average' => $price > $overallAverage,
                'is_below_average' => $price < $overallAverage
            ];
        }
        
        return [
            'route' => "{$origin}-{$destination}",
            'service_level' => $serviceLevel,
            'period_days' => $days,
            'overall_average' => $overallAverage,
            'carrier_averages' => $averages,
            'lowest_price' => $prices->min(),
            'highest_price' => $prices->max(),
            'price_range' => $prices->max() - $prices->min(),
            'competitor_count' => $prices->count()
        ];
    }

    public static function getCompetitiveAnalysis(string $origin, string $destination, int $days = 30): array
    {
        $routeData = self::where('origin_country', $origin)
            ->where('destination_country', $destination)
            ->where('collected_at', '>=', now()->subDays($days))
            ->get()
            ->groupBy('carrier_name');
            
        if ($routeData->isEmpty()) {
            return [
                'route' => "{$origin}-{$destination}",
                'analysis_period' => $days,
                'competitors_found' => 0,
                'message' => 'No competitor data available for this route'
            ];
        }
        
        $analysis = [
            'route' => "{$origin}-{$destination}",
            'analysis_period' => $days,
            'competitors_found' => $routeData->count(),
            'service_levels' => [],
            'price_competitive' => [],
            'market_insights' => []
        ];
        
        foreach ($routeData as $carrier => $prices) {
            $serviceLevels = $prices->groupBy('service_level');
            
            foreach ($serviceLevels as $serviceLevel => $servicePrices) {
                $avgPrice = $servicePrices->avg('price');
                $priceCount = $servicePrices->count();
                
                $analysis['service_levels'][] = [
                    'carrier' => $carrier,
                    'service_level' => $serviceLevel,
                    'average_price' => $avgPrice,
                    'price_count' => $priceCount,
                    'price_range' => [
                        'min' => $servicePrices->min('price'),
                        'max' => $servicePrices->max('price')
                    ]
                ];
            }
        }
        
        return $analysis;
    }

    public static function addCompetitorPrice(string $carrier, string $origin, string $destination, string $serviceLevel, float $price, string $currency = 'USD', ?float $weight = null, string $sourceType = 'api'): self
    {
        return self::create([
            'carrier_name' => $carrier,
            'origin_country' => $origin,
            'destination_country' => $destination,
            'service_level' => $serviceLevel,
            'price' => $price,
            'currency' => $currency,
            'weight_kg' => $weight,
            'source_type' => $sourceType,
            'collected_at' => now()
        ]);
    }

    public static function getPriceAlerts(string $carrier, string $origin, string $destination, int $days = 7): array
    {
        $recentPrices = self::byCarrier($carrier)
            ->byRoute($origin, $destination)
            ->where('collected_at', '>=', now()->subDays($days))
            ->get()
            ->groupBy('service_level');
            
        $alerts = [];
        
        foreach ($recentPrices as $serviceLevel => $prices) {
            if ($prices->count() < 2) {
                continue;
            }
            
            $sortedPrices = $prices->sortBy('collected_at');
            $latestPrice = $sortedPrices->last();
            $previousPrice = $sortedPrices->take(-2)->first();
            
            $changeAmount = $latestPrice->price - $previousPrice->price;
            $changePercentage = ($changeAmount / $previousPrice->price) * 100;
            
            if (abs($changePercentage) >= 10) { // Alert for 10% or more change
                $alerts[] = [
                    'carrier' => $carrier,
                    'route' => "{$origin}-{$destination}",
                    'service_level' => $serviceLevel,
                    'change_amount' => $changeAmount,
                    'change_percentage' => $changePercentage,
                    'is_increase' => $changeAmount > 0,
                    'is_decrease' => $changeAmount < 0,
                    'previous_price' => $previousPrice->price,
                    'current_price' => $latestPrice->price,
                    'change_date' => $latestPrice->collected_at->format('Y-m-d')
                ];
            }
        }
        
        return $alerts;
    }
}