<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactPerformanceMetrics extends Model
{
    protected $table = 'fact_performance_metrics';
    public $timestamps = false;
    protected $primaryKey = 'metric_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'metric_key',
        'branch_key',
        'driver_key',
        'route_key',
        'metric_date_key',
        'metric_hour',
        'kpi_category',
        'metric_name',
        'metric_value',
        'metric_unit',
        'target_value',
        'variance_from_target',
        'performance_score',
        'status_flag',
        'notes',
        'data_quality_score',
        'last_updated'
    ];

    protected $casts = [
        'metric_date_key' => 'integer',
        'metric_hour' => 'integer',
        'metric_value' => 'decimal:4',
        'target_value' => 'decimal:4',
        'variance_from_target' => 'decimal:4',
        'performance_score' => 'decimal:4',
        'data_quality_score' => 'decimal:2',
        'last_updated' => 'datetime'
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(DimensionBranch::class, 'branch_key', 'branch_key');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DimensionDriver::class, 'driver_key', 'driver_key');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DimensionRoute::class, 'route_key', 'route_key');
    }

    public function metricDate(): BelongsTo
    {
        return $this->belongsTo(DimensionDate::class, 'metric_date_key', 'date_key');
    }

    // Scopes for reporting
    public function scopeByCategory($query, $category)
    {
        return $query->where('kpi_category', $category);
    }

    public function scopeByMetricName($query, $name)
    {
        return $query->where('metric_name', $name);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date_key', [$startDate, $endDate]);
    }

    public function scopeByHour($query, $hour)
    {
        return $query->where('metric_hour', $hour);
    }

    public function scopeAboveTarget($query)
    {
        return $query->whereColumn('metric_value', '>', 'target_value');
    }

    public function scopeBelowTarget($query)
    {
        return $query->whereColumn('metric_value', '<', 'target_value');
    }

    public function scopeOnTarget($query)
    {
        return $query->whereColumn('metric_value', '=', 'target_value');
    }

    // Helper methods
    public function isAboveTarget(): bool
    {
        return $this->metric_value > $this->target_value;
    }

    public function isBelowTarget(): bool
    {
        return $this->metric_value < $this->target_value;
    }

    public function getVariancePercentage(): float
    {
        return $this->target_value > 0 ? ($this->variance_from_target / $this->target_value) * 100 : 0;
    }

    public function getPerformanceStatus(): string
    {
        if ($this->performance_score >= 90) return 'Excellent';
        if ($this->performance_score >= 80) return 'Good';
        if ($this->performance_score >= 70) return 'Fair';
        return 'Poor';
    }
}