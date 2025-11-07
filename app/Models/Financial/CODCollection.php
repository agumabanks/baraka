<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CODCollection extends Model
{
    protected $fillable = [
        'shipment_id',
        'client_key',
        'cod_amount',
        'collection_status',
        'collected_amount',
        'collection_date',
        'due_date',
        'days_overdue',
        'aging_bucket',
        'collection_method',
        'reference_number',
        'notes',
        'dunning_level',
        'last_dunning_date',
        'write_off_amount',
        'write_off_date',
        'collection_fee',
        'net_collection',
        'currency_code'
    ];

    protected $casts = [
        'cod_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'collection_date' => 'date',
        'due_date' => 'date',
        'days_overdue' => 'integer',
        'write_off_amount' => 'decimal:2',
        'write_off_date' => 'date',
        'collection_fee' => 'decimal:2',
        'net_collection' => 'decimal:2',
        'last_dunning_date' => 'date'
    ];

    // Aging bucket constants
    const AGING_CURRENT = 'current';
    const AGING_1_30 = '1-30';
    const AGING_31_60 = '31-60';
    const AGING_61_90 = '61-90';
    const AGING_90_PLUS = '90+';

    // Collection status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COLLECTED = 'collected';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_WRITTEN_OFF = 'written_off';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_PARTIAL = 'partial';

    // Dunning levels
    const DUNNING_NONE = 0;
    const DUNNING_LEVEL_1 = 1;
    const DUNNING_LEVEL_2 = 2;
    const DUNNING_LEVEL_3 = 3;
    const DUNNING_FINAL = 4;

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\FactShipment::class, 'shipment_key', 'shipment_key');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionClient::class, 'client_key', 'client_key');
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('collection_status', self::STATUS_OVERDUE)
                    ->orWhere('days_overdue', '>', 0);
    }

    public function scopeByAgingBucket($query, $bucket)
    {
        return $query->where('aging_bucket', $bucket);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('collection_status', $status);
    }

    public function scopeCollected($query)
    {
        return $query->where('collection_status', self::STATUS_COLLECTED);
    }

    // Helper methods
    public function isOverdue(): bool
    {
        return $this->days_overdue > 0;
    }

    public function isCollected(): bool
    {
        return $this->collection_status === self::STATUS_COLLECTED;
    }

    public function getAgingBucket(): string
    {
        if ($this->days_overdue <= 0) {
            return self::AGING_CURRENT;
        } elseif ($this->days_overdue <= 30) {
            return self::AGING_1_30;
        } elseif ($this->days_overdue <= 60) {
            return self::AGING_31_60;
        } elseif ($this->days_overdue <= 90) {
            return self::AGING_61_90;
        } else {
            return self::AGING_90_PLUS;
        }
    }

    public function calculateDaysOverdue(): int
    {
        if ($this->isCollected() || $this->collection_status === self::STATUS_PENDING) {
            return 0;
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getCollectionRate(): float
    {
        if ($this->cod_amount <= 0) {
            return 0;
        }

        return ($this->collected_amount / $this->cod_amount) * 100;
    }

    public function needsDunning(): bool
    {
        return $this->days_overdue > 30 && $this->collection_status !== self::STATUS_COLLECTED;
    }
}