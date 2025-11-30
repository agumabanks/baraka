<?php

namespace App\Models;

use App\Models\BranchWorker;
use App\Models\Backend\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverAssignment extends Model
{
    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'route_id',
        'assignment_date',
        'assigned_shipments',
        'assigned_weight_kg',
        'assigned_distance_km',
        'estimated_duration_minutes',
        'status',
        'started_at',
        'completed_at',
        'completed_shipments',
        'failed_shipments',
        'actual_distance_km',
        'actual_duration_minutes',
        'efficiency_score',
        'metadata',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'assigned_weight_kg' => 'decimal:2',
        'assigned_distance_km' => 'decimal:2',
        'actual_distance_km' => 'decimal:2',
        'efficiency_score' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(BranchWorker::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'assigned_worker_id', 'driver_id')
            ->whereDate('assigned_at', $this->assignment_date);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('assignment_date', $date);
    }

    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Calculate completion rate
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->assigned_shipments === 0) {
            return 0;
        }
        
        return ($this->completed_shipments / $this->assigned_shipments) * 100;
    }

    /**
     * Calculate efficiency (actual vs estimated)
     */
    public function calculateEfficiency(): float
    {
        if (!$this->actual_duration_minutes || $this->estimated_duration_minutes === 0) {
            return 100;
        }
        
        // Efficiency = (estimated / actual) * 100
        // > 100 = faster than expected, < 100 = slower
        return ($this->estimated_duration_minutes / $this->actual_duration_minutes) * 100;
    }

    /**
     * Mark assignment as started
     */
    public function start(): self
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        
        return $this;
    }

    /**
     * Mark assignment as completed
     */
    public function complete(array $metrics = []): self
    {
        $this->update(array_merge([
            'status' => 'completed',
            'completed_at' => now(),
            'efficiency_score' => $this->calculateEfficiency(),
        ], $metrics));
        
        return $this;
    }

    /**
     * Check if driver has capacity for more shipments
     */
    public function hasCapacity(int $maxShipments = 50, float $maxWeight = 1000): bool
    {
        return $this->assigned_shipments < $maxShipments &&
               $this->assigned_weight_kg < $maxWeight;
    }

    /**
     * Add shipment to assignment
     */
    public function addShipment(Shipment $shipment, float $distanceKm = 0): self
    {
        $this->increment('assigned_shipments');
        $this->increment('assigned_weight_kg', $shipment->chargeable_weight_kg ?? $shipment->weight ?? 0);
        $this->increment('assigned_distance_km', $distanceKm);
        
        // Update estimated duration (assume 15 min per stop + travel time)
        $travelMinutes = ($distanceKm / 30) * 60; // 30 km/h average
        $this->increment('estimated_duration_minutes', 15 + $travelMinutes);
        
        return $this;
    }
}
