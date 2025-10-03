<?php

namespace App\Models\Backend;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factory\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BranchWorker extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'branch_id',
        'user_id',
        'role',
        'permissions',
        'work_schedule',
        'hourly_rate',
        'assigned_at',
        'unassigned_at',
        'notes',
        'metadata',
        'status',
    ];

    protected $casts = [
        'permissions' => 'array',
        'work_schedule' => 'array',
        'hourly_rate' => 'decimal:2',
        'assigned_at' => 'date',
        'unassigned_at' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('BranchWorker')
            ->logOnly(['role', 'assigned_at', 'unassigned_at', 'status'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} branch worker: " . ($this->user ? $this->user->name : 'Unknown'));
    }

    // Relationships

    /**
     * The branch this worker is assigned to
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * The user account for this worker
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Shipments assigned to this worker
     */
    public function assignedShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'assigned_worker_id');
    }

    /**
     * Tasks assigned to this worker
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_worker_id');
    }

    /**
     * Work logs for this worker
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class, 'worker_id');
    }

    // Scopes

    /**
     * Scope: Only active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE)
                    ->whereNull('unassigned_at');
    }

    /**
     * Scope: Currently assigned (not unassigned)
     */
    public function scopeCurrentlyAssigned($query)
    {
        return $query->whereNull('unassigned_at');
    }

    /**
     * Scope: By role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: Assigned within date range
     */
    public function scopeAssignedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('assigned_at', [$startDate, $endDate]);
    }

    /**
     * Scope: With specific permission
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->whereJsonContains('permissions', $permission);
    }

    // Accessors & Mutators

    /**
     * Get worker's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'Unknown Worker';
    }

    /**
     * Get worker's email
     */
    public function getEmailAttribute(): string
    {
        return $this->user ? $this->user->email : '';
    }

    /**
     * Get branch name
     */
    public function getBranchNameAttribute(): string
    {
        return $this->branch ? $this->branch->name : 'Unassigned';
    }

    /**
     * Get formatted role
     */
    public function getFormattedRoleAttribute(): string
    {
        return __('branch_worker.roles.' . $this->role, [], 'en');
    }

    /**
     * Get assignment duration in days
     */
    public function getAssignmentDurationAttribute(): ?int
    {
        if (!$this->assigned_at) {
            return null;
        }

        $endDate = $this->unassigned_at ?? now();
        return $this->assigned_at->diffInDays($endDate);
    }

    /**
     * Check if worker is currently active
     */
    public function getIsCurrentlyActiveAttribute(): bool
    {
        return $this->status === Status::ACTIVE && is_null($this->unassigned_at);
    }

    // Business Logic Methods

    /**
     * Check if worker has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions) {
            return false;
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Check if worker can perform an action
     */
    public function canPerform(string $action): bool
    {
        if (!$this->isCurrentlyActive) {
            return false;
        }

        // Check role-based permissions
        $rolePermissions = $this->getRolePermissions();
        if (in_array($action, $rolePermissions)) {
            return true;
        }

        // Check specific permissions
        return $this->hasPermission($action);
    }

    /**
     * Get permissions for the worker's role
     */
    protected function getRolePermissions(): array
    {
        return match($this->role) {
            'dispatcher' => [
                'view_shipments',
                'assign_shipments',
                'update_shipment_status',
                'create_tasks',
                'view_reports',
            ],
            'driver' => [
                'view_assigned_shipments',
                'update_shipment_status',
                'pod_upload',
                'view_route',
            ],
            'supervisor' => [
                'view_shipments',
                'assign_shipments',
                'update_shipment_status',
                'manage_workers',
                'view_reports',
                'approve_time_off',
            ],
            'warehouse_worker' => [
                'view_shipments',
                'update_shipment_status',
                'manage_inventory',
                'process_returns',
            ],
            'customer_service' => [
                'view_shipments',
                'update_shipment_status',
                'handle_complaints',
                'process_refunds',
            ],
            default => [
                'view_assigned_shipments',
                'update_shipment_status',
            ],
        };
    }

    /**
     * Get worker's current workload
     */
    public function getCurrentWorkload(): array
    {
        $activeShipments = $this->assignedShipments()
            ->whereIn('status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->count();

        $pendingTasks = $this->assignedTasks()
            ->where('status', 'pending')
            ->count();

        $todayShipments = $this->assignedShipments()
            ->whereDate('created_at', today())
            ->count();

        return [
            'active_shipments' => $activeShipments,
            'pending_tasks' => $pendingTasks,
            'today_shipments' => $todayShipments,
            'capacity_utilization' => $this->calculateCapacityUtilization(),
        ];
    }

    /**
     * Calculate worker capacity utilization
     */
    protected function calculateCapacityUtilization(): float
    {
        $activeShipments = $this->assignedShipments()
            ->whereIn('status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->count();

        // Assume max capacity of 10 shipments per worker
        $maxCapacity = 10;
        return min(100.0, ($activeShipments / $maxCapacity) * 100);
    }

    /**
     * Get worker performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $completedShipments = $this->assignedShipments()
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->where('status', 'delivered')
            ->count();

        $onTimeDeliveries = $this->assignedShipments()
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->where('status', 'delivered')
            ->whereRaw('delivered_at <= expected_delivery_date')
            ->count();

        $averageDeliveryTime = $this->assignedShipments()
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, delivered_at)) as avg_hours')
            ->first();

        return [
            'completed_shipments_30_days' => $completedShipments,
            'on_time_delivery_rate' => $completedShipments > 0 ? ($onTimeDeliveries / $completedShipments) * 100 : 0,
            'average_delivery_time_hours' => $averageDeliveryTime ? (float) $averageDeliveryTime->avg_hours : null,
            'workload_efficiency' => $this->calculateWorkloadEfficiency(),
        ];
    }

    /**
     * Calculate workload efficiency
     */
    protected function calculateWorkloadEfficiency(): float
    {
        $metrics = $this->getPerformanceMetrics();
        $workload = $this->getCurrentWorkload();

        // Simple efficiency calculation based on on-time delivery rate and capacity utilization
        $onTimeRate = $metrics['on_time_delivery_rate'] / 100;
        $capacityRate = $workload['capacity_utilization'] / 100;

        return ($onTimeRate * 0.7 + $capacityRate * 0.3) * 100;
    }

    /**
     * Check if worker is available for assignment
     */
    public function isAvailable(): bool
    {
        if (!$this->isCurrentlyActive) {
            return false;
        }

        // Check work schedule
        if ($this->work_schedule && !$this->isWithinWorkSchedule()) {
            return false;
        }

        // Check capacity (max 10 active shipments)
        $activeShipments = $this->assignedShipments()
            ->whereIn('status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->count();

        return $activeShipments < 10;
    }

    /**
     * Check if current time is within worker's schedule
     */
    protected function isWithinWorkSchedule(): bool
    {
        if (!$this->work_schedule) {
            return true; // No schedule means always available
        }

        $now = now();
        $dayOfWeek = (int) $now->format('w');
        $currentTime = $now->format('H:i');

        if (!isset($this->work_schedule[$dayOfWeek])) {
            return false;
        }

        $schedule = $this->work_schedule[$dayOfWeek];
        return $currentTime >= $schedule['start'] && $currentTime <= $schedule['end'];
    }

    /**
     * Assign shipment to worker
     */
    public function assignShipment(Shipment $shipment): bool
    {
        if (!$this->canPerform('assign_shipments')) {
            return false;
        }

        if (!$this->isAvailable()) {
            return false;
        }

        $shipment->update([
            'assigned_worker_id' => $this->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        // Log the assignment
        activity()
            ->performedOn($shipment)
            ->causedBy($this->user)
            ->withProperties([
                'worker_id' => $this->id,
                'worker_name' => $this->full_name,
            ])
            ->log("Shipment assigned to worker: {$this->full_name}");

        return true;
    }

    /**
     * Unassign worker from branch
     */
    public function unassign(): bool
    {
        if ($this->unassigned_at) {
            return false; // Already unassigned
        }

        // Reassign active shipments to other workers
        $this->reassignActiveShipments();

        $this->update([
            'unassigned_at' => now(),
            'status' => Status::INACTIVE,
        ]);

        return true;
    }

    /**
     * Reassign active shipments to other available workers
     */
    protected function reassignActiveShipments(): void
    {
        $activeShipments = $this->assignedShipments()
            ->whereIn('status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->get();

        foreach ($activeShipments as $shipment) {
            // Find another available worker in the same branch
            $availableWorker = $this->branch->activeWorkers()
                ->where('user_id', '!=', $this->user_id)
                ->whereHas('user', function ($q) {
                    $q->where('status', Status::ACTIVE);
                })
                ->first();

            if ($availableWorker) {
                $shipment->update([
                    'assigned_worker_id' => $availableWorker->id,
                ]);

                activity()
                    ->performedOn($shipment)
                    ->log("Shipment reassigned due to worker unassignment");
            }
        }
    }

    /**
     * Get worker's schedule for the week
     */
    public function getWeeklySchedule(): array
    {
        if (!$this->work_schedule) {
            return [];
        }

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $schedule = [];
        foreach ($this->work_schedule as $dayIndex => $hours) {
            $schedule[$days[$dayIndex]] = [
                'start' => $hours['start'] ?? null,
                'end' => $hours['end'] ?? null,
                'is_working_day' => isset($hours['start']) && isset($hours['end']),
            ];
        }

        return $schedule;
    }

    /**
     * Get status badge for UI
     */
    public function getStatusBadgeAttribute(): string
    {
        if (!$this->isCurrentlyActive) {
            return '<span class="badge badge-secondary">Inactive</span>';
        }

        return '<span class="badge badge-success">Active</span>';
    }
}
