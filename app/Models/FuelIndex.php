<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class FuelIndex extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'source',
        'index_value',
        'region',
        'effective_date'
    ];

    protected $casts = [
        'index_value' => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('FuelIndex')
            ->logOnly(['source', 'index_value', 'effective_date', 'region'])
            ->setDescriptionForEvent(fn (string $eventName) => "Fuel index {$eventName}");
    }

    // Scopes
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByRegion($query, string $region = null)
    {
        return $region ? $query->where('region', $region) : $query;
    }

    public function scopeCurrent($query)
    {
        return $query->where('effective_date', '<=', Carbon::now());
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('effective_date', 'desc');
    }

    public function scopeEffectiveOn($query, Carbon $date)
    {
        return $query->where('effective_date', '<=', $date);
    }

    // Business Logic
    public function getCurrentFuelIndex(string $region = null): ?float
    {
        $query = self::query()
            ->bySource('eia')
            ->current()
            ->latest()
            ->limit(1);

        if ($region) {
            $query->byRegion($region);
        }

        $fuelIndex = $query->first();
        
        return $fuelIndex ? $fuelIndex->index_value : null;
    }

    public function getFuelIndexPercentage(): float
    {
        // Calculate percentage change from base index (100)
        return (($this->index_value - 100) / 100) * 100;
    }

    public function getSurchargeRate(float $baseIndex = 100.0): float
    {
        if ($this->index_value <= $baseIndex) {
            return 0;
        }
        
        // 8% of the difference between current and base index
        return (($this->index_value - $baseIndex) / $baseIndex) * 0.08;
    }

    public function getFormattedIndex(): string
    {
        return number_format($this->index_value, 2);
    }

    public function isCurrent(): bool
    {
        return $this->effective_date->isToday() || $this->effective_date->isFuture();
    }

    public function isOutdated(): bool
    {
        return $this->effective_date->isBefore(Carbon::now()->subDays(30));
    }

    public static function getLatestIndex(string $source = 'eia', string $region = null): ?self
    {
        $query = self::bySource($source)->latest();
        
        if ($region) {
            $query->byRegion($region);
        }
        
        return $query->first();
    }

    public static function updateIndex(string $source, float $value, string $region = null, ?Carbon $effectiveDate = null): self
    {
        $effectiveDate = $effectiveDate ?? Carbon::today();
        
        return self::updateOrCreate(
            [
                'source' => $source,
                'effective_date' => $effectiveDate,
                'region' => $region
            ],
            [
                'index_value' => $value
            ]
        );
    }

    public static function getHistoricalData(int $months = 12): \Illuminate\Support\Collection
    {
        return self::where('effective_date', '>=', Carbon::now()->subMonths($months))
            ->orderBy('effective_date')
            ->get()
            ->groupBy('source');
    }

    public static function getIndexTrend(string $source = 'eia', int $days = 30): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);
        
        $data = self::bySource($source)
            ->whereBetween('effective_date', [$startDate, $endDate])
            ->orderBy('effective_date')
            ->get();
            
        if ($data->isEmpty()) {
            return [];
        }
        
        $trend = [];
        $previousValue = null;
        
        foreach ($data as $index) {
            $change = $previousValue ? $index->index_value - $previousValue : 0;
            $percentageChange = $previousValue ? ($change / $previousValue) * 100 : 0;
            
            $trend[] = [
                'date' => $index->effective_date->format('Y-m-d'),
                'value' => $index->index_value,
                'change' => $change,
                'percentage_change' => $percentageChange,
                'is_increase' => $change > 0,
                'is_decrease' => $change < 0
            ];
            
            $previousValue = $index->index_value;
        }
        
        return $trend;
    }
}