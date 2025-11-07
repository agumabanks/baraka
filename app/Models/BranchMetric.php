<?php

namespace App\Models;

use App\Enums\BranchStatus;
use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'snapshot_date',
        'window',
        'throughput_count',
        'capacity_utilization',
        'exception_rate',
        'on_time_rate',
        'average_processing_time_hours',
        'on_time_target',
        'alerts_triggered',
        'metadata',
        'calculated_at',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'capacity_utilization' => 'float',
        'exception_rate' => 'float',
        'on_time_rate' => 'float',
        'average_processing_time_hours' => 'float',
        'on_time_target' => 'float',
        'metadata' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function isAtRisk(float $exceptionThreshold = 0.2, float $utilizationThreshold = 0.4): bool
    {
        return $this->exception_rate >= $exceptionThreshold
            || ($this->capacity_utilization <= $utilizationThreshold && $this->branch?->status === BranchStatus::ACTIVE->toLegacy());
    }

    public function performanceLabel(): string
    {
        if ($this->exception_rate >= 0.2) {
            return 'exception_risk';
        }

        if ($this->capacity_utilization >= 1.1) {
            return 'over_capacity';
        }

        if ($this->capacity_utilization <= 0.4) {
            return 'underutilized';
        }

        return 'healthy';
    }
}
