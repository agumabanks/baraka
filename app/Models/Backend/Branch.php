<?php

namespace App\Models\Backend;

use App\Enums\BranchStatus;
use App\Enums\BranchType;
use App\Enums\ShipmentStatus;
use App\Enums\Status;
use App\Models\BranchAlert;
use App\Models\BranchMetric;
use App\Models\Driver;
use App\Models\Client;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_hub',
        'parent_branch_id',
        'address',
        'phone',
        'email',
        'time_zone',
        'latitude',
        'longitude',
        'geo_lat',
        'geo_lng',
        'operating_hours',
        'capabilities',
        'capacity_parcels_per_day',
        'metadata',
        'status',
    ];

    protected $casts = [
        'is_hub' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'geo_lat' => 'decimal:8',
        'geo_lng' => 'decimal:8',
        'operating_hours' => 'array',
        'capabilities' => 'array',
        'metadata' => 'array',
        'capacity_parcels_per_day' => 'integer',
    ];

    protected $appends = [
        'status_label',
        'status_enum',
        'branch_type',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Branch')
            ->logOnly(['name', 'code', 'type', 'is_hub', 'status'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} branch: {$this->name}");
    }

    // Relationships

    /**
     * Parent branch in hierarchy
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_branch_id');
    }

    /**
     * Child branches in hierarchy
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_branch_id');
    }

    /**
     * Branch manager for this branch
     */
    public function branchManager(): HasOne
    {
        return $this->hasOne(BranchManager::class, 'branch_id');
    }

    /**
     * All workers assigned to this branch
     */
    public function branchWorkers(): HasMany
    {
        return $this->hasMany(BranchWorker::class, 'branch_id');
    }

    /**
     * Active workers for this branch
     */
    public function activeWorkers(): HasMany
    {
        return $this->branchWorkers()->where('status', Status::ACTIVE)->whereNull('unassigned_at');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(BranchMetric::class, 'branch_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(BranchAlert::class, 'branch_id');
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class, 'branch_id');
    }

    /**
     * Shipments originating from this branch
     */
    public function originShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'origin_branch_id');
    }

    /**
     * Shipments destined for this branch
     */
    public function destinationShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'dest_branch_id');
    }

    /**
     * Clients primarily served by this branch
     */
    public function primaryClients(): HasMany
    {
        return $this->hasMany(Client::class, 'primary_branch_id');
    }

    // Scopes

    /**
     * Scope: Only active branches
     */
    public function scopeActive($query)
    {
        return $query->where('status', BranchStatus::ACTIVE->toLegacy());
    }

    /**
     * Scope: Only HUB branches
     */
    public function scopeHub($query)
    {
        return $query->where('is_hub', true);
    }

    /**
     * Scope: Only non-HUB branches
     */
    public function scopeNonHub($query)
    {
        return $query->where('is_hub', false);
    }

    /**
     * Scope: By branch type
     */
    public function scopeType($query, string $type)
    {
        $normalized = BranchType::fromString($type)->value;

        return $query->where('type', $normalized);
    }

    /**
     * Scope: Root level branches (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_branch_id');
    }

    /**
     * Scope: Child branches of a specific parent
     */
    public function scopeChildrenOf($query, int $parentId)
    {
        return $query->where('parent_branch_id', $parentId);
    }

    /**
     * Scope: Branches within a certain distance (requires spatial query)
     */
    public function scopeWithinDistance($query, float $lat, float $lng, float $distanceKm)
    {
        // This would require spatial functions in the database
        // For now, return all - implement spatial queries later
        return $query;
    }

    // Accessors & Mutators

    /**
     * Get the branch's full hierarchy path
     */
    public function getHierarchyPathAttribute(): string
    {
        $path = [$this->name];
        $current = $this;

        while ($current->parent) {
            array_unshift($path, $current->parent->name);
            $current = $current->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get branch level in hierarchy (0 = root, 1 = child of root, etc.)
     */
    public function getHierarchyLevelAttribute(): int
    {
        $level = 0;
        $current = $this;

        while ($current->parent) {
            $level++;
            $current = $current->parent;
        }

        return $level;
    }

    /**
     * Check if branch has specific capability
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    /**
     * Get all descendants in hierarchy
     */
    public function getAllDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Get all ancestors in hierarchy
     */
    public function getAllAncestors()
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Check if this branch can serve shipments to another branch
     */
    public function canServeBranch(Branch $otherBranch): bool
    {
        // HUB can serve all branches
        if ($this->is_hub) {
            return true;
        }

        // Regional branches can serve their children and siblings
        if ($this->type === BranchType::REGIONAL_BRANCH->value) {
            return $otherBranch->parent_branch_id === $this->id ||
                   $otherBranch->parent_branch_id === $this->parent_branch_id;
        }

        // Local branches can only serve themselves
        return $this->id === $otherBranch->id;
    }

    /**
     * Get branch capacity metrics
     */
    public function getCapacityMetrics(): array
    {
        return [
            'active_workers' => $this->activeWorkers()->count(),
            'pending_shipments' => $this->originShipments()
                ->whereIn('current_status', array_map(fn (ShipmentStatus $status) => $status->value, [
                    ShipmentStatus::BOOKED,
                    ShipmentStatus::PICKUP_SCHEDULED,
                    ShipmentStatus::PICKED_UP,
                    ShipmentStatus::AT_ORIGIN_HUB,
                ]))->count(),
            'active_clients' => $this->primaryClients()->where('status', Status::ACTIVE)->count(),
            'utilization_rate' => $this->calculateUtilizationRate(),
            'capacity_limit' => $this->capacity_parcels_per_day,
        ];
    }

    /**
     * Calculate branch utilization rate
     */
    protected function calculateUtilizationRate(): float
    {
        $activeWorkers = $this->activeWorkers()->count();
        $pendingShipments = $this->originShipments()
            ->whereIn('current_status', array_map(fn (ShipmentStatus $status) => $status->value, [
                ShipmentStatus::BOOKED,
                ShipmentStatus::PICKUP_SCHEDULED,
                ShipmentStatus::PICKED_UP,
            ]))->count();

        if ($this->capacity_parcels_per_day) {
            $ratio = $pendingShipments / max(1, $this->capacity_parcels_per_day);
            return round(min(100, $ratio * 100), 2);
        }

        if ($activeWorkers === 0) {
            return 0.0;
        }

        // Fallback utilisation using workers (10 shipments capacity per worker baseline)
        return round(min(100.0, ($pendingShipments / ($activeWorkers * 10)) * 100), 2);
    }

    /**
     * Get branch performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $totalShipments = $this->originShipments()->count();
        $deliveredShipments = $this->originShipments()
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->count();
        $onTimeDeliveries = $this->originShipments()
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->whereRaw('delivered_at <= expected_delivery_date')
            ->count();

        return [
            'total_shipments' => $totalShipments,
            'delivery_rate' => $totalShipments > 0 ? ($deliveredShipments / $totalShipments) * 100 : 0,
            'on_time_delivery_rate' => $deliveredShipments > 0 ? ($onTimeDeliveries / $deliveredShipments) * 100 : 0,
            'average_processing_time' => $this->calculateAverageProcessingTime(),
        ];
    }

    /**
     * Calculate average processing time for shipments
     */
    protected function calculateAverageProcessingTime(): ?float
    {
        $avgTime = $this->originShipments()
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, delivered_at)) as avg_hours')
            ->first();

        return $avgTime ? (float) $avgTime->avg_hours : null;
    }

    /**
     * Check if branch is operational during given time
     */
    public function isOperational(\DateTime $datetime = null): bool
    {
        if ($this->status !== BranchStatus::ACTIVE->toLegacy()) {
            return false;
        }

        if (!$this->operating_hours) {
            return true; // Assume 24/7 if no hours specified
        }

        $datetime = $datetime ?? new \DateTime();
        $dayOfWeek = (int) $datetime->format('w'); // 0 = Sunday, 6 = Saturday
        $currentTime = $datetime->format('H:i');

        $hours = $this->operating_hours;
        if (!isset($hours[$dayOfWeek])) {
            return false;
        }

        $dayHours = $hours[$dayOfWeek];
        return $currentTime >= $dayHours['open'] && $currentTime <= $dayHours['close'];
    }

    /**
     * Get distance to another branch (simplified calculation)
     */
    public function distanceTo(Branch $otherBranch): float
    {
        if (!$this->latitude || !$this->longitude || !$otherBranch->latitude || !$otherBranch->longitude) {
            return 0.0;
        }

        // Haversine formula for distance calculation
        $earthRadius = 6371; // km

        $latDelta = deg2rad($otherBranch->latitude - $this->latitude);
        $lngDelta = deg2rad($otherBranch->longitude - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($otherBranch->latitude)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getBranchTypeAttribute(): string
    {
        return BranchType::fromString($this->type)->value;
    }

    public function getStatusLabelAttribute(): string
    {
        return BranchStatus::fromLegacy((int) $this->status)->label();
    }

    public function getStatusEnumAttribute(): string
    {
        return BranchStatus::fromLegacy((int) $this->status)->value;
    }

    public function scopeWithType($query, BranchType|string $type)
    {
        $value = $type instanceof BranchType ? $type->value : BranchType::fromString($type)->value;

        return $query->where('type', $value);
    }
}
