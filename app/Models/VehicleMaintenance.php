<?php

namespace App\Models;

use App\Models\Backend\Branch;
use App\Models\Backend\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMaintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_maintenance';

    protected $fillable = [
        'vehicle_id',
        'branch_id',
        'reported_by_user_id',
        'performed_by_user_id',
        'maintenance_type',
        'category',
        'status',
        'description',
        'work_performed',
        'scheduled_at',
        'started_at',
        'completed_at',
        'odometer_reading',
        'next_service_at',
        'parts_cost',
        'labor_cost',
        'total_cost',
        'invoice_number',
        'service_provider',
        'mechanic_name',
        'priority',
        'notes',
        'parts_used',
        'attachments',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'parts_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'odometer_reading' => 'integer',
        'next_service_at' => 'integer',
        'parts_used' => 'array',
        'attachments' => 'array',
    ];

    // Relationships

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    // Scopes

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<', now());
    }

    // Business Logic

    /**
     * Start maintenance work
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Update vehicle status
        $this->vehicle->update(['status' => 'maintenance']);

        activity()
            ->performedOn($this)
            ->log('Maintenance started');
    }

    /**
     * Complete maintenance work
     */
    public function complete(array $data = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'work_performed' => $data['work_performed'] ?? $this->work_performed,
            'parts_cost' => $data['parts_cost'] ?? $this->parts_cost,
            'labor_cost' => $data['labor_cost'] ?? $this->labor_cost,
            'total_cost' => ($data['parts_cost'] ?? $this->parts_cost) + ($data['labor_cost'] ?? $this->labor_cost),
            'odometer_reading' => $data['odometer_reading'] ?? $this->odometer_reading,
        ]);

        // Update vehicle
        $this->vehicle->update([
            'status' => 'active',
            'current_odometer' => $data['odometer_reading'] ?? $this->vehicle->current_odometer,
            'last_maintenance_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->log('Maintenance completed');
    }

    /**
     * Cancel maintenance
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
            ->log('Maintenance cancelled');
    }

    /**
     * Get maintenance duration in hours
     */
    public function getDurationHoursAttribute(): ?float
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInHours($this->completed_at, true);
    }

    /**
     * Check if overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at && 
               $this->scheduled_at->isPast();
    }
}
