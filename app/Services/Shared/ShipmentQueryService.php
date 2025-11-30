<?php

namespace App\Services\Shared;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared Shipment Query Service
 * 
 * Provides consistent query building for shipments across Admin and Branch modules.
 * Ensures canonical field usage and status filtering.
 */
class ShipmentQueryService
{
    /**
     * Get base shipment query with standard eager loading
     */
    public function baseQuery(): Builder
    {
        return Shipment::query()
            ->with([
                'customer:id,name,email',
                'originBranch:id,name,code',
                'destBranch:id,name,code',
                'assignedWorker.user:id,name',
            ]);
    }

    /**
     * Filter shipments by branch (origin or destination)
     */
    public function forBranch(Builder $query, int $branchId, string $direction = 'all'): Builder
    {
        return $query->where(function ($q) use ($branchId, $direction) {
            if ($direction === 'inbound') {
                $q->where('dest_branch_id', $branchId);
            } elseif ($direction === 'outbound') {
                $q->where('origin_branch_id', $branchId);
            } else {
                $q->where('origin_branch_id', $branchId)
                  ->orWhere('dest_branch_id', $branchId);
            }
        });
    }

    /**
     * Filter shipments by status (canonical field)
     */
    public function withStatus(Builder $query, ShipmentStatus|string|null $status): Builder
    {
        if (!$status) {
            return $query;
        }

        if ($status instanceof ShipmentStatus) {
            return $query->where('current_status', $status->value);
        }

        $statusEnum = ShipmentStatus::fromString($status);
        if ($statusEnum) {
            return $query->where('current_status', $statusEnum->value);
        }

        // Fallback: use as-is if not recognized
        return $query->where('current_status', $status);
    }

    /**
     * Filter shipments by multiple statuses
     */
    public function withStatuses(Builder $query, array $statuses): Builder
    {
        $values = collect($statuses)->map(function ($status) {
            if ($status instanceof ShipmentStatus) {
                return $status->value;
            }
            
            $enum = ShipmentStatus::fromString($status);
            return $enum ? $enum->value : $status;
        })->filter()->unique()->values()->toArray();

        return $query->whereIn('current_status', $values);
    }

    /**
     * Filter active (non-terminal) shipments
     */
    public function activeShipments(Builder $query): Builder
    {
        $activeStatuses = array_map(
            fn($status) => $status->value,
            ShipmentStatus::activeStatuses()
        );

        return $query->whereIn('current_status', $activeStatuses);
    }

    /**
     * Filter shipments at risk of SLA breach
     */
    public function atRisk(Builder $query, ?int $hoursThreshold = 24): Builder
    {
        $threshold = now()->addHours($hoursThreshold);

        return $query->where(function ($q) use ($threshold) {
            $q->whereNotNull('expected_delivery_date')
                ->where(function ($inner) use ($threshold) {
                    // Already late
                    $inner->where(function ($late) {
                        $late->whereNotNull('delivered_at')
                            ->whereColumn('delivered_at', '>', 'expected_delivery_date');
                    })
                    // Or at risk
                    ->orWhere(function ($risk) use ($threshold) {
                        $risk->whereNull('delivered_at')
                            ->where('expected_delivery_date', '<=', $threshold);
                    });
                });
        });
    }

    /**
     * Filter shipments by date range
     */
    public function dateRange(Builder $query, ?string $from, ?string $to, string $field = 'created_at'): Builder
    {
        if ($from) {
            $query->whereDate($field, '>=', $from);
        }

        if ($to) {
            $query->whereDate($field, '<=', $to);
        }

        return $query;
    }

    /**
     * Search shipments by tracking number or waybill
     */
    public function search(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('tracking_number', 'like', "%{$search}%")
              ->orWhere('waybill_number', 'like', "%{$search}%")
              ->orWhere('reference_number', 'like', "%{$search}%");
        });
    }

    /**
     * Get shipments requiring action
     */
    public function requiresAction(Builder $query, int $branchId): Builder
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('origin_branch_id', $branchId)
              ->orWhere('dest_branch_id', $branchId);
        })
        ->where(function ($q) {
            // Unassigned pickups
            $q->where(function ($unassigned) {
                $unassigned->whereIn('current_status', [
                    ShipmentStatus::BOOKED->value,
                    ShipmentStatus::PICKUP_SCHEDULED->value,
                ])
                ->whereNull('assigned_worker_id');
            })
            // Exceptions
            ->orWhere('current_status', ShipmentStatus::EXCEPTION->value)
            // At risk
            ->orWhere(function ($atRisk) {
                $atRisk->whereNotNull('expected_delivery_date')
                    ->where('expected_delivery_date', '<=', now()->addHours(24))
                    ->whereNull('delivered_at');
            });
        });
    }

    /**
     * Get common statistics for shipments
     */
    public function getStats(int $branchId): array
    {
        $base = Shipment::where(function ($q) use ($branchId) {
            $q->where('origin_branch_id', $branchId)
              ->orWhere('dest_branch_id', $branchId);
        });

        return [
            'total' => (clone $base)->count(),
            
            'in_transit' => (clone $base)->whereIn('current_status', [
                ShipmentStatus::AT_ORIGIN_HUB->value,
                ShipmentStatus::LINEHAUL_DEPARTED->value,
                ShipmentStatus::LINEHAUL_ARRIVED->value,
                ShipmentStatus::AT_DESTINATION_HUB->value,
            ])->count(),
            
            'delivered_today' => (clone $base)
                ->where('current_status', ShipmentStatus::DELIVERED->value)
                ->whereDate('delivered_at', today())
                ->count(),
            
            'at_risk' => (clone $base)
                ->whereNotNull('expected_delivery_date')
                ->where('expected_delivery_date', '<=', now()->addHours(24))
                ->whereNull('delivered_at')
                ->count(),
            
            'exceptions' => (clone $base)
                ->where('current_status', ShipmentStatus::EXCEPTION->value)
                ->count(),
            
            'inbound' => Shipment::where('dest_branch_id', $branchId)
                ->whereNotIn('current_status', [
                    ShipmentStatus::DELIVERED->value,
                    ShipmentStatus::CANCELLED->value,
                ])
                ->count(),
            
            'outbound' => Shipment::where('origin_branch_id', $branchId)
                ->whereNotIn('current_status', [
                    ShipmentStatus::DELIVERED->value,
                    ShipmentStatus::CANCELLED->value,
                ])
                ->count(),

            'awaiting_pickup' => (clone $base)
                ->whereIn('current_status', [
                    ShipmentStatus::BOOKED->value,
                    ShipmentStatus::PICKUP_SCHEDULED->value,
                ])
                ->count(),
        ];
    }
}
