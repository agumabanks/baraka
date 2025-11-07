<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceLevelDefinition extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'description',
        'base_multiplier',
        'min_delivery_hours',
        'max_delivery_hours',
        'reliability_score',
        'sla_claims_covered'
    ];

    protected $casts = [
        'base_multiplier' => 'decimal:2',
        'reliability_score' => 'decimal:2',
        'sla_claims_covered' => 'boolean',
        'min_delivery_hours' => 'integer',
        'max_delivery_hours' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ServiceLevelDefinition')
            ->logOnly(['code', 'name', 'base_multiplier'])
            ->setDescriptionForEvent(fn (string $eventName) => "Service level definition {$eventName}");
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query;
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    // Business Logic
    public function getDeliveryWindow(): array
    {
        return [
            'min_hours' => $this->min_delivery_hours,
            'max_hours' => $this->max_delivery_hours,
            'display_text' => $this->getDeliveryWindowText()
        ];
    }

    public function getDeliveryWindowText(): string
    {
        if ($this->min_delivery_hours && $this->max_delivery_hours) {
            if ($this->min_delivery_hours < 24) {
                return "{$this->min_delivery_hours}-{$this->max_delivery_hours} hours";
            } else {
                $minDays = round($this->min_delivery_hours / 24, 1);
                $maxDays = round($this->max_delivery_hours / 24, 1);
                return "{$minDays}-{$maxDays} days";
            }
        }
        
        return 'Standard delivery';
    }

    public function getPriceMultiplier(): float
    {
        return $this->base_multiplier;
    }

    public function getSlaGuarantee(): bool
    {
        return $this->sla_claims_covered;
    }
}