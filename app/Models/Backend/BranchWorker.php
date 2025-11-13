<?php

namespace App\Models\Backend;

use App\Enums\BranchWorkerRole;
use App\Enums\EmploymentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\Status;
use App\Services\Logistics\ShipmentLifecycleService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BranchWorker extends Model
{
    use LogsActivity;

    protected $fillable = [
        'branch_id',
        'user_id',
        'role',
        'designation',
        'employment_status',
        'contact_phone',
        'id_number',
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
        'employment_status' => EmploymentStatus::class,
        'permissions' => 'array',
        'work_schedule' => 'array',
        'hourly_rate' => 'decimal:2',
        'assigned_at' => 'date',
        'unassigned_at' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = [
        'full_name',
        'first_name',
        'last_name',
        'email',
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
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Shipments assigned to this worker
     */
    public function assignedShipments(): HasMany
    {
        return $this->hasMany(\App\Models\Shipment::class, 'assigned_worker_id');
    }

    /**
     * Tasks assigned to this worker
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(\App\Models\Task::class, 'assigned_worker_id');
    }

    /**
     * Work logs for this worker
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(\App\Models\WorkLog::class, 'worker_id');
    }

    // Scopes

    /**
     * Scope: Only active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE)
                    ->whereNull('unassigned_at')
                    ->where(function ($q) {
                        $q->whereNull('employment_status')
                          ->orWhereIn('employment_status', [
                              EmploymentStatus::ACTIVE->value,
                              EmploymentStatus::PROBATION->value,
                          ]);
                    });
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
    public function scopeByRole($query, BranchWorkerRole|string $role)
    {
        $value = $role instanceof BranchWorkerRole ? $role->value : BranchWorkerRole::fromString($role)->value;

        return $query->where('role', $value);
    }

    public function scopeWithEmploymentStatus($query, EmploymentStatus|string $status)
    {
        $value = $status instanceof EmploymentStatus ? $status->value : EmploymentStatus::fromString($status)->value;

        return $query->where('employment_status', $value);
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

    public function getRoleAttribute(?string $value): ?BranchWorkerRole
    {
        if ($value === null) {
            return null;
        }

        return BranchWorkerRole::fromString($value);
    }

    public function setRoleAttribute(BranchWorkerRole|string|null $value): void
    {
        if ($value instanceof BranchWorkerRole) {
            $this->attributes['role'] = $value->value;

            return;
        }

        if ($value === null || $value === '') {
            $this->attributes['role'] = null;

            return;
        }

        $this->attributes['role'] = BranchWorkerRole::fromString($value)->value;
    }

    /**
     * Get worker's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'Unknown Worker';
    }

    /**
     * Legacy compatibility: first name accessor.
     */
    public function getFirstNameAttribute(): string
    {
        $name = $this->resolveUserName();

        if ($name === '') {
            return '';
        }

        $segments = preg_split('/\s+/', $name, 2) ?: [];

        return $segments[0] ?? $name;
    }

    /**
     * Legacy compatibility: last name accessor.
     */
    public function getLastNameAttribute(): string
    {
        $name = $this->resolveUserName();

        if ($name === '') {
            return '';
        }

        $segments = preg_split('/\s+/', $name, 2) ?: [];

        return $segments[1] ?? '';
    }

    /**
     * Get worker's email
     */
    public function getEmailAttribute(): string
    {
        return $this->user ? $this->user->email : '';
    }

    protected function resolveUserName(): string
    {
        $user = $this->relationLoaded('user') ? $this->getRelation('user') : $this->user;

        return trim((string) ($user->name ?? ''));
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
        $role = $this->role;

        if (!$role instanceof BranchWorkerRole) {
            $role = BranchWorkerRole::fromString((string) $role);
        }

        return $role->label();
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
        $employmentStatus = $this->employment_status instanceof EmploymentStatus
            ? $this->employment_status
            : ($this->employment_status ? EmploymentStatus::fromString((string) $this->employment_status) : EmploymentStatus::ACTIVE);

        return $this->status === Status::ACTIVE
            && is_null($this->unassigned_at)
            && in_array($employmentStatus, [EmploymentStatus::ACTIVE, EmploymentStatus::PROBATION], true);
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
        $role = $this->role instanceof BranchWorkerRole
            ? $this->role
            : BranchWorkerRole::fromString((string) $this->role);

        return match($role) {
            BranchWorkerRole::DISPATCHER => [
                'view_shipments',
                'assign_shipments',
                'update_shipment_status',
                'create_tasks',
                'view_reports',
            ],
            BranchWorkerRole::DRIVER, BranchWorkerRole::COURIER => [
                'view_assigned_shipments',
                'update_shipment_status',
                'pod_upload',
                'view_route',
            ],
            BranchWorkerRole::OPS_SUPERVISOR, BranchWorkerRole::BRANCH_MANAGER => [
                'view_shipments',
                'assign_shipments',
                'update_shipment_status',
                'manage_workers',
                'view_reports',
                'approve_time_off',
            ],
            BranchWorkerRole::SORTATION_AGENT => [
                'view_shipments',
                'update_shipment_status',
                'manage_inventory',
                'process_returns',
            ],
            BranchWorkerRole::CUSTOMER_SUPPORT => [
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
            ->whereIn('current_status', $this->assignmentStatusValues())
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
            ->whereIn('current_status', $this->assignmentStatusValues())
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
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->count();

        $onTimeDeliveries = $this->assignedShipments()
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->whereRaw('delivered_at <= expected_delivery_date')
            ->count();

        $averageDeliveryTime = $this->assignedShipments()
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, delivered_at)) as avg_hours')
            ->value('avg_hours');

        $workload = $this->getCurrentWorkload();
        $onTimeRate = $completedShipments > 0 ? ($onTimeDeliveries / $completedShipments) * 100 : 0;
        $workloadEfficiency = $this->calculateWorkloadEfficiency($onTimeRate, $workload['capacity_utilization']);

        return [
            'completed_shipments_30_days' => $completedShipments,
            'on_time_delivery_rate' => $onTimeRate,
            'average_delivery_time_hours' => $averageDeliveryTime ? (float) $averageDeliveryTime : null,
            'workload_efficiency' => $workloadEfficiency,
        ];
    }

    /**
     * Calculate workload efficiency
     */
    protected function calculateWorkloadEfficiency(float $onTimeRate, float $capacityUtilization): float
    {
        $normalizedOnTime = $onTimeRate / 100;
        $normalizedCapacity = $capacityUtilization / 100;

        return round(($normalizedOnTime * 0.7 + $normalizedCapacity * 0.3) * 100, 2);
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
            ->whereIn('current_status', $this->assignmentStatusValues())
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
    public function assignShipment(\App\Models\Shipment $shipment): bool
    {
        if (!$this->canPerform('assign_shipments')) {
            return false;
        }

        if (!$this->isAvailable()) {
            return false;
        }

        $shipment->forceFill([
            'assigned_worker_id' => $this->id,
            'assigned_at' => now(),
        ])->save();

        app(ShipmentLifecycleService::class)->transition($shipment->fresh(), ShipmentStatus::PICKUP_SCHEDULED, [
            'trigger' => 'branch_worker.assign',
            'performed_by' => $this->user?->id,
            'timestamp' => now(),
            'location_type' => 'branch',
            'location_id' => $this->branch_id,
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
            ->whereIn('current_status', $this->assignmentStatusValues())
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

    private function assignmentStatusValues(): array
    {
        $statuses = array_merge(
            ShipmentStatus::pickupStages(),
            ShipmentStatus::transportStages(),
            ShipmentStatus::deliveryStages(),
            ShipmentStatus::returnStages()
        );

        $filtered = array_filter($statuses, fn (ShipmentStatus $status) => ! $status->isTerminal());

        return array_map(fn (ShipmentStatus $status) => $status->value, $filtered);
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

    public function getEmploymentStatusLabelAttribute(): string
    {
        $status = $this->employment_status instanceof EmploymentStatus
            ? $this->employment_status
            : ($this->employment_status ? EmploymentStatus::fromString((string) $this->employment_status) : EmploymentStatus::ACTIVE);

        return $status->label();
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
