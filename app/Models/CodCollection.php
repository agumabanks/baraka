<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodCollection extends Model
{
    protected $fillable = [
        'shipment_id',
        'collected_by',
        'branch_id',
        'expected_amount',
        'collected_amount',
        'currency',
        'exchange_rate',
        'collection_method',
        'payment_reference',
        'status',
        'collected_at',
        'verified_at',
        'remitted_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'collected_at' => 'datetime',
        'verified_at' => 'datetime',
        'remitted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCollected($query)
    {
        return $query->where('status', 'collected');
    }

    public function scopeNeedsVerification($query)
    {
        return $query->where('status', 'collected')->whereNull('verified_at');
    }

    public function scopeNeedsRemittance($query)
    {
        return $query->whereIn('status', ['collected', 'verified'])->whereNull('remitted_at');
    }

    /**
     * Mark as collected
     */
    public function markCollected(float $amount, string $method, ?string $reference = null): self
    {
        $this->update([
            'collected_amount' => $amount,
            'collection_method' => $method,
            'payment_reference' => $reference,
            'status' => 'collected',
            'collected_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as verified
     */
    public function markVerified(): self
    {
        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as remitted
     */
    public function markRemitted(): self
    {
        $this->update([
            'status' => 'remitted',
            'remitted_at' => now(),
        ]);

        return $this;
    }

    /**
     * Check if there's a discrepancy
     */
    public function hasDiscrepancy(): bool
    {
        if (!$this->collected_amount) {
            return false;
        }

        return abs($this->expected_amount - $this->collected_amount) > 0.01;
    }

    /**
     * Get discrepancy amount
     */
    public function getDiscrepancyAttribute(): float
    {
        return round($this->collected_amount - $this->expected_amount, 2);
    }
}
