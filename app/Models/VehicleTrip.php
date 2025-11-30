<?php

namespace App\Models;

use App\Models\Backend\Branch;
use App\Models\Backend\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleTrip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_number',
        'vehicle_id',
        'driver_id',
        'branch_id',
        'origin_branch_id',
        'destination_branch_id',
        'trip_type',
        'route_name',
        'status',
        'planned_start_at',
        'planned_end_at',
        'actual_start_at',
        'actual_end_at',
        'planned_distance_km',
        'actual_distance_km',
        'fuel_consumption_liters',
        'total_stops',
        'completed_stops',
        'shipment_count',
        'total_weight_kg',
        'cargo_manifest',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'planned_start_at' => 'datetime',
        'planned_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'planned_distance_km' => 'decimal:2',
        'actual_distance_km' => 'decimal:2',
        'fuel_consumption_liters' => 'decimal:2',
        'total_weight_kg' => 'decimal:2',
        'cargo_manifest' => 'array',
        'metadata' => 'array',
        'total_stops' => 'integer',
        'completed_stops' => 'integer',
        'shipment_count' => 'integer',
    ];

    // Relationships

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TripStop::class, 'trip_id')->orderBy('sequence');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planned', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    // Business Logic

    /**
     * Start the trip
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'actual_start_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->log('Trip started');
    }

    /**
     * Complete the trip
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'actual_end_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->log('Trip completed');
    }

    /**
     * Cancel the trip
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $this->notes . "\nCancelled: " . $reason,
        ]);

        activity()
            ->performedOn($this)
            ->withProperties(['reason' => $reason])
            ->log('Trip cancelled');
    }

    /**
     * Get trip duration in hours
     */
    public function getDurationHoursAttribute(): ?float
    {
        if (!$this->actual_start_at || !$this->actual_end_at) {
            return null;
        }

        return $this->actual_start_at->diffInHours($this->actual_end_at, true);
    }

    /**
     * Get trip progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_stops === 0) {
            return 0;
        }

        return (int) round(($this->completed_stops / $this->total_stops) * 100);
    }

    /**
     * Check if trip is on time
     */
    public function getIsOnTimeAttribute(): bool
    {
        if ($this->status === 'completed' && $this->actual_end_at && $this->planned_end_at) {
            return $this->actual_end_at->lte($this->planned_end_at);
        }

        if ($this->status === 'in_progress' && $this->planned_end_at) {
            return now()->lte($this->planned_end_at);
        }

        return true;
    }

    /**
     * Get fuel efficiency (km per liter)
     */
    public function getFuelEfficiencyAttribute(): ?float
    {
        if (!$this->actual_distance_km || !$this->fuel_consumption_liters || $this->fuel_consumption_liters == 0) {
            return null;
        }

        return round($this->actual_distance_km / $this->fuel_consumption_liters, 2);
    }

    /**
     * Generate unique trip number
     */
    public static function generateTripNumber(int $branchId): string
    {
        $prefix = 'TRP';
        $branchCode = str_pad($branchId, 3, '0', STR_PAD_LEFT);
        $date = now()->format('ymd');
        
        $lastTrip = static::where('trip_number', 'like', "{$prefix}-{$branchCode}-{$date}-%")
            ->orderByDesc('trip_number')
            ->first();

        if ($lastTrip) {
            $lastSequence = (int) substr($lastTrip->trip_number, -4);
            $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }

        return "{$prefix}-{$branchCode}-{$date}-{$sequence}";
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($trip) {
            if (!$trip->trip_number) {
                $trip->trip_number = static::generateTripNumber($trip->branch_id);
            }
        });
    }
}
