<?php

namespace App\Models;

use App\Models\Backend\Branch;
use App\Models\Concerns\BranchScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Consolidation Model
 * 
 * Represents a mother shipment grouping multiple baby shipments
 * Supports both BBX (physical) and LBX (virtual) consolidation
 */
class Consolidation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, BranchScoped;

    protected $fillable = [
        'branch_id',
        'consolidation_number',
        'type',
        'destination',
        'destination_branch_id',
        'status',
        'max_pieces',
        'max_weight_kg',
        'max_volume_cbm',
        'cutoff_time',
        'current_pieces',
        'current_weight_kg',
        'current_volume_cbm',
        'transport_mode',
        'awb_number',
        'container_number',
        'vehicle_number',
        'locked_at',
        'dispatched_at',
        'arrived_at',
        'deconsolidation_started_at',
        'completed_at',
        'created_by',
        'locked_by',
        'dispatched_by',
        'metadata',
    ];

    protected $casts = [
        'max_weight_kg' => 'decimal:2',
        'max_volume_cbm' => 'decimal:3',
        'current_weight_kg' => 'decimal:2',
        'current_volume_cbm' => 'decimal:3',
        'cutoff_time' => 'datetime',
        'locked_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'arrived_at' => 'datetime',
        'deconsolidation_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = ['is_open', 'is_full', 'utilization_percentage'];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('consolidation')
            ->logOnly(['consolidation_number', 'type', 'status', 'current_pieces'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} consolidation");
    }

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    /**
     * Baby shipments in this consolidation
     */
    public function babyShipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'consolidation_shipments')
            ->withPivot(['sequence_number', 'weight_kg', 'volume_cbm', 'status', 'added_at', 'removed_at'])
            ->withTimestamps()
            ->wherePivot('status', '!=', 'REMOVED');
    }

    /**
     * All shipments including removed ones
     */
    public function allShipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'consolidation_shipments')
            ->withPivot(['sequence_number', 'weight_kg', 'volume_cbm', 'status', 'added_at', 'removed_at'])
            ->withTimestamps();
    }

    /**
     * Deconsolidation events
     */
    public function deconsolidationEvents(): HasMany
    {
        return $this->hasMany(DeconsolidationEvent::class);
    }

    // Accessors

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'OPEN';
    }

    public function getIsFullAttribute(): bool
    {
        if ($this->max_pieces && $this->current_pieces >= $this->max_pieces) {
            return true;
        }

        if ($this->max_weight_kg && $this->current_weight_kg >= $this->max_weight_kg) {
            return true;
        }

        if ($this->max_volume_cbm && $this->current_volume_cbm >= $this->max_volume_cbm) {
            return true;
        }

        return false;
    }

    public function getUtilizationPercentageAttribute(): float
    {
        $percentages = [];

        if ($this->max_pieces) {
            $percentages[] = ($this->current_pieces / $this->max_pieces) * 100;
        }

        if ($this->max_weight_kg) {
            $percentages[] = ($this->current_weight_kg / $this->max_weight_kg) * 100;
        }

        if ($this->max_volume_cbm) {
            $percentages[] = ($this->current_volume_cbm / $this->max_volume_cbm) * 100;
        }

        return empty($percentages) ? 0 : max($percentages);
    }

    // Business Logic

    /**
     * Check if consolidation can accept more shipments
     */
    public function canAcceptShipment(Shipment $shipment): bool
    {
        if ($this->status !== 'OPEN') {
            return false;
        }

        if ($this->is_full) {
            return false;
        }

        if ($this->cutoff_time && now()->isAfter($this->cutoff_time)) {
            return false;
        }

        // Check if adding this shipment would exceed capacity
        $totalWeight = $shipment->parcels->sum('weight_kg');
        if ($this->max_weight_kg && ($this->current_weight_kg + $totalWeight) > $this->max_weight_kg) {
            return false;
        }

        return true;
    }

    /**
     * Add a baby shipment to this consolidation
     */
    public function addShipment(Shipment $shipment, User $addedBy): bool
    {
        if (!$this->canAcceptShipment($shipment)) {
            return false;
        }

        $weight = $shipment->parcels->sum('weight_kg');
        $volume = $shipment->parcels->sum('volume_cbm') ?? 0;

        $this->babyShipments()->attach($shipment->id, [
            'sequence_number' => $this->current_pieces + 1,
            'weight_kg' => $weight,
            'volume_cbm' => $volume,
            'status' => 'ADDED',
            'added_at' => now(),
            'added_by' => $addedBy->id,
        ]);

        // Update consolidation totals
        $this->increment('current_pieces');
        $this->increment('current_weight_kg', $weight);
        $this->increment('current_volume_cbm', $volume);

        // Update shipment
        $shipment->update([
            'consolidation_id' => $this->id,
            'consolidation_type' => $this->type,
        ]);

        activity()
            ->performedOn($this)
            ->causedBy($addedBy)
            ->withProperties(['shipment_id' => $shipment->id, 'weight_kg' => $weight])
            ->log("Shipment {$shipment->tracking_number} added to consolidation");

        return true;
    }

    /**
     * Remove a baby shipment from consolidation (before lock)
     */
    public function removeShipment(Shipment $shipment, User $removedBy): bool
    {
        if ($this->status !== 'OPEN') {
            return false;
        }

        $pivot = $this->babyShipments()->where('shipment_id', $shipment->id)->first()?->pivot;

        if (!$pivot) {
            return false;
        }

        // Mark as removed in pivot
        $this->babyShipments()->updateExistingPivot($shipment->id, [
            'status' => 'REMOVED',
            'removed_at' => now(),
        ]);

        // Update consolidation totals
        $this->decrement('current_pieces');
        $this->decrement('current_weight_kg', $pivot->weight_kg);
        $this->decrement('current_volume_cbm', $pivot->volume_cbm ?? 0);

        // Update shipment
        $shipment->update([
            'consolidation_id' => null,
            'consolidation_type' => 'individual',
        ]);

        activity()
            ->performedOn($this)
            ->causedBy($removedBy)
            ->withProperties(['shipment_id' => $shipment->id])
            ->log("Shipment {$shipment->tracking_number} removed from consolidation");

        return true;
    }

    /**
     * Lock consolidation (no more additions allowed)
     */
    public function lock(User $lockedBy): bool
    {
        if ($this->status !== 'OPEN') {
            return false;
        }

        if ($this->current_pieces === 0) {
            return false;
        }

        $this->update([
            'status' => 'LOCKED',
            'locked_at' => now(),
            'locked_by' => $lockedBy->id,
        ]);

        // Update all baby shipments status
        $this->babyShipments()->each(function ($shipment) {
            $this->babyShipments()->updateExistingPivot($shipment->id, [
                'status' => 'LOCKED',
            ]);
        });

        activity()
            ->performedOn($this)
            ->causedBy($lockedBy)
            ->log("Consolidation locked with {$this->current_pieces} shipments");

        return true;
    }

    /**
     * Dispatch consolidation
     */
    public function dispatch(User $dispatchedBy, ?string $awbNumber = null, ?string $vehicleNumber = null): bool
    {
        if ($this->status !== 'LOCKED') {
            return false;
        }

        $this->update([
            'status' => 'IN_TRANSIT',
            'dispatched_at' => now(),
            'dispatched_by' => $dispatchedBy->id,
            'awb_number' => $awbNumber ?? $this->awb_number,
            'vehicle_number' => $vehicleNumber ?? $this->vehicle_number,
        ]);

        // Update baby shipments status
        $this->babyShipments()->each(function ($shipment) {
            $this->babyShipments()->updateExistingPivot($shipment->id, [
                'status' => 'IN_TRANSIT',
            ]);
        });

        activity()
            ->performedOn($this)
            ->causedBy($dispatchedBy)
            ->log("Consolidation dispatched");

        return true;
    }

    /**
     * Mark consolidation as arrived at destination
     */
    public function markArrived(User $user): bool
    {
        if ($this->status !== 'IN_TRANSIT') {
            return false;
        }

        $this->update([
            'status' => 'ARRIVED',
            'arrived_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->causedBy($user)
            ->log("Consolidation arrived at destination");

        return true;
    }

    /**
     * Start deconsolidation process
     */
    public function startDeconsolidation(User $user): bool
    {
        if (!in_array($this->status, ['ARRIVED', 'IN_TRANSIT'])) {
            return false;
        }

        $this->update([
            'status' => 'DECONSOLIDATING',
            'deconsolidation_started_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->causedBy($user)
            ->log("Deconsolidation started");

        return true;
    }

    /**
     * Complete deconsolidation
     */
    public function completeDeconsolidation(User $user): bool
    {
        if ($this->status !== 'DECONSOLIDATING') {
            return false;
        }

        $this->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
        ]);

        // Update baby shipments status
        $this->babyShipments()->each(function ($shipment) {
            $this->babyShipments()->updateExistingPivot($shipment->id, [
                'status' => 'DECONSOLIDATED',
            ]);
        });

        activity()
            ->performedOn($this)
            ->causedBy($user)
            ->log("Deconsolidation completed");

        return true;
    }

    // Scopes

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'LOCKED');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'IN_TRANSIT');
    }

    public function scopeForDestination($query, string $destination)
    {
        return $query->where('destination', $destination);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
