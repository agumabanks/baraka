<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevenueRecognition extends Model
{
    protected $fillable = [
        'shipment_key',
        'client_key',
        'transaction_key',
        'total_revenue',
        'recognized_revenue',
        'deferred_revenue',
        'recognition_percentage',
        'recognition_date',
        'service_completion_date',
        'recognition_method',
        'revenue_stream',
        'service_type',
        'recognition_period_start',
        'recognition_period_end',
        'is_fully_recognized',
        'accrual_type',
        'currency_code',
        'exchange_rate',
        'base_currency_amount',
        'remaining_periods',
        'notes'
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'recognized_revenue' => 'decimal:2',
        'deferred_revenue' => 'decimal:2',
        'recognition_percentage' => 'decimal:4',
        'recognition_date' => 'date',
        'service_completion_date' => 'date',
        'recognition_period_start' => 'date',
        'recognition_period_end' => 'date',
        'is_fully_recognized' => 'boolean',
        'exchange_rate' => 'decimal:6',
        'base_currency_amount' => 'decimal:2',
        'remaining_periods' => 'integer'
    ];

    // Recognition method constants
    const METHOD_POINT_IN_TIME = 'point_in_time';
    const METHOD_OVER_TIME = 'over_time';
    const METHOD_STRAIGHT_LINE = 'straight_line';
    const METHOD_PERCENTAGE_COMPLETE = 'percentage_complete';
    const METHOD_MILESTONE = 'milestone';

    // Accrual type constants
    const ACCRUAL_REVENUE = 'revenue';
    const ACCRUAL_EXPENSE = 'expense';
    const ACCRUAL_DEFERRED = 'deferred';

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\FactShipment::class, 'shipment_key', 'shipment_key');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionClient::class, 'client_key', 'client_key');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\FactFinancialTransaction::class, 'transaction_key', 'transaction_key');
    }

    public function recognitionEvents(): HasMany
    {
        return $this->hasMany(RevenueRecognitionEvent::class, 'revenue_recognition_id');
    }

    // Scopes
    public function scopeRecognized($query)
    {
        return $query->where('is_fully_recognized', true);
    }

    public function scopePendingRecognition($query)
    {
        return $query->where('is_fully_recognized', false);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('recognition_method', $method);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('recognition_date', [$startDate, $endDate]);
    }

    public function scopeDeferred($query)
    {
        return $query->where('deferred_revenue', '>', 0);
    }

    // Helper methods
    public function calculateRecognitionRate(): float
    {
        if ($this->total_revenue <= 0) {
            return 0;
        }

        return ($this->recognized_revenue / $this->total_revenue) * 100;
    }

    public function getRemainingAmount(): float
    {
        return $this->total_revenue - $this->recognized_revenue;
    }

    public function isRecognitionComplete(): bool
    {
        return $this->is_fully_recognized;
    }

    public function getDaysInService(): int
    {
        if (!$this->recognition_period_start || !$this->recognition_period_end) {
            return 0;
        }

        return $this->recognition_period_start->diffInDays($this->recognition_period_end) + 1;
    }

    public function shouldRecognizeToday(): bool
    {
        if ($this->is_fully_recognized) {
            return false;
        }

        return now()->gte($this->service_completion_date ?? now());
    }

    public function calculateAccrualAmount(float $baseAmount, string $accrualType): float
    {
        return match($accrualType) {
            self::ACCRUAL_REVENUE => $baseAmount,
            self::ACCRUAL_EXPENSE => -$baseAmount,
            self::ACCRUAL_DEFERRED => 0,
            default => 0
        };
    }

    public function updateRecognition(float $amount, ?string $notes = null): void
    {
        $this->recognized_revenue += $amount;
        $this->recognition_date = now();
        
        if ($this->recognized_revenue >= $this->total_revenue) {
            $this->is_fully_recognized = true;
            $this->deferred_revenue = 0;
        } else {
            $this->deferred_revenue = $this->getRemainingAmount();
        }

        if ($notes) {
            $this->notes = $this->notes ? $this->notes . ' | ' . $notes : $notes;
        }

        $this->save();
    }
}