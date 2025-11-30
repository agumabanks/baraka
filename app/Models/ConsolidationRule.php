<?php

namespace App\Models;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ConsolidationRule Model
 * 
 * Defines automatic consolidation rules for matching and grouping shipments
 */
class ConsolidationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'rule_name',
        'is_active',
        'priority',
        'destination_country',
        'destination_city',
        'destination_branch_id',
        'service_level',
        'consolidation_type',
        'min_pieces',
        'max_pieces',
        'max_weight_kg',
        'max_age_hours',
        'schedule',
        'default_cutoff_time',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_weight_kg' => 'decimal:2',
        'schedule' => 'array',
        'default_cutoff_time' => 'datetime',
    ];

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Helper methods

    /**
     * Check if shipment matches this rule
     */
    public function matchesShipment(Shipment $shipment): bool
    {
        // Check destination country
        if ($this->destination_country && $shipment->destination_country !== $this->destination_country) {
            return false;
        }

        // Check destination city
        if ($this->destination_city && $shipment->destination_city !== $this->destination_city) {
            return false;
        }

        // Check destination branch
        if ($this->destination_branch_id && $shipment->destination_branch_id !== $this->destination_branch_id) {
            return false;
        }

        // Check service level
        if ($this->service_level && $shipment->service_level !== $this->service_level) {
            return false;
        }

        return true;
    }

    /**
     * Get cutoff time for today
     */
    public function getCutoffTimeForDate(\DateTime $date): ?\DateTime
    {
        $dayName = strtolower($date->format('l')); // monday, tuesday, etc.

        if ($this->schedule && isset($this->schedule[$dayName])) {
            $time = $this->schedule[$dayName];
            $cutoff = clone $date;
            
            // Parse time (HH:MM format)
            [$hour, $minute] = explode(':', $time);
            $cutoff->setTime((int)$hour, (int)$minute);
            
            return $cutoff;
        }

        if ($this->default_cutoff_time) {
            $cutoff = clone $date;
            $cutoff->setTime(
                $this->default_cutoff_time->hour,
                $this->default_cutoff_time->minute
            );
            return $cutoff;
        }

        return null;
    }
}
