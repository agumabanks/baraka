<?php

namespace App\Models;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeconsolidationEvent Model
 * 
 * Tracks the unpacking process of consolidations at destination
 */
class DeconsolidationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'consolidation_id',
        'shipment_id',
        'branch_id',
        'event_type',
        'notes',
        'discrepancy_data',
        'performed_by',
        'occurred_at',
    ];

    protected $casts = [
        'discrepancy_data' => 'array',
        'occurred_at' => 'datetime',
    ];

    // Relationships

    public function consolidation(): BelongsTo
    {
        return $this->belongsTo(Consolidation::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Scopes

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeDiscrepancies($query)
    {
        return $query->where('event_type', 'DISCREPANCY');
    }

    public function scopeForConsolidation($query, int $consolidationId)
    {
        return $query->where('consolidation_id', $consolidationId);
    }

    // Helper methods

    public function hasDiscrepancy(): bool
    {
        return $this->event_type === 'DISCREPANCY' && !empty($this->discrepancy_data);
    }
}
