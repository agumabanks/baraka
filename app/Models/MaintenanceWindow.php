<?php

namespace App\Models;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MaintenanceWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'branch_id',
        'maintenance_type',
        'status',
        'scheduled_start_at',
        'scheduled_end_at',
        'actual_start_at',
        'actual_end_at',
        'capacity_impact_percent',
        'description',
        'notes',
        'affected_services',
        'created_by',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'affected_services' => 'array',
        'capacity_impact_percent' => 'integer',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if maintenance window is currently active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'in_progress' && $this->status !== 'scheduled') {
            return false;
        }

        $now = now();
        return $now->between($this->scheduled_start_at, $this->scheduled_end_at);
    }

    /**
     * Get capacity factor (0-100) during maintenance
     */
    public function getAvailableCapacityPercent(): int
    {
        if (!$this->isActive()) {
            return 100;
        }

        return max(0, 100 - $this->capacity_impact_percent);
    }

    /**
     * Mark maintenance as started
     */
    public function markStarted(): void
    {
        $this->update([
            'status' => 'in_progress',
            'actual_start_at' => now(),
        ]);
    }

    /**
     * Mark maintenance as completed
     */
    public function markCompleted(?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'actual_end_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Cancel maintenance window
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason ? "Cancelled: {$reason}" : $this->notes,
        ]);
    }

    /**
     * Scope to get active maintenance windows
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress'])
            ->where('scheduled_start_at', '<=', now())
            ->where('scheduled_end_at', '>=', now());
    }

    /**
     * Scope to get upcoming maintenance
     */
    public function scopeUpcoming($query, int $hoursAhead = 48)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_start_at', '>', now())
            ->where('scheduled_start_at', '<=', now()->addHours($hoursAhead));
    }

    /**
     * Scope by branch
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope by entity
     */
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }
}
