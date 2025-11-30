<?php

namespace App\Http\Controllers\Shared;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipment\UpdateShipmentStatusRequest;
use App\Models\Shipment;
use App\Services\Logistics\ShipmentLifecycleService;
use App\Services\Shared\ShipmentQueryService;
use Illuminate\Http\RedirectResponse;

/**
 * Base Shipment Controller
 * 
 * Shared functionality for both Admin and Branch shipment controllers.
 * Promotes code reuse and consistent behavior across modules.
 */
abstract class BaseShipmentController extends Controller
{
    protected ShipmentQueryService $queryService;
    protected ShipmentLifecycleService $lifecycleService;

    public function __construct(
        ShipmentQueryService $queryService,
        ShipmentLifecycleService $lifecycleService
    ) {
        $this->queryService = $queryService;
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Update shipment status using shared FormRequest
     */
    protected function performStatusUpdate(
        UpdateShipmentStatusRequest $request,
        Shipment $shipment
    ): RedirectResponse {
        $status = $request->getStatus();
        $context = $request->getLifecycleContext();

        try {
            $this->lifecycleService->transition($shipment, $status, $context);

            return redirect()
                ->back()
                ->with('success', "Shipment {$shipment->tracking_number} updated to {$status->label()}");

        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withErrors(['status' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Get shipment statistics for a branch
     */
    protected function getShipmentStats(int $branchId): array
    {
        return $this->queryService->getStats($branchId);
    }

    /**
     * Build shipment query with common filters
     */
    protected function buildShipmentQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->queryService->baseQuery();

        if (isset($filters['branch_id'])) {
            $query = $this->queryService->forBranch(
                $query,
                $filters['branch_id'],
                $filters['direction'] ?? 'all'
            );
        }

        if (isset($filters['status'])) {
            $query = $this->queryService->withStatus($query, $filters['status']);
        }

        if (isset($filters['search'])) {
            $query = $this->queryService->search($query, $filters['search']);
        }

        if (isset($filters['from']) || isset($filters['to'])) {
            $query = $this->queryService->dateRange(
                $query,
                $filters['from'] ?? null,
                $filters['to'] ?? null
            );
        }

        if (!empty($filters['at_risk'])) {
            $query = $this->queryService->atRisk($query, $filters['risk_hours'] ?? 24);
        }

        if (!empty($filters['active_only'])) {
            $query = $this->queryService->activeShipments($query);
        }

        return $query;
    }

    /**
     * Validate shipment belongs to branch
     */
    protected function assertShipmentBelongsToBranch(Shipment $shipment, int $branchId): void
    {
        if ($shipment->origin_branch_id !== $branchId && $shipment->dest_branch_id !== $branchId) {
            abort(403, 'Shipment does not belong to this branch');
        }
    }

    /**
     * Get available status transitions for a shipment
     */
    protected function getAvailableTransitions(Shipment $shipment): array
    {
        $currentStatus = $shipment->current_status instanceof ShipmentStatus
            ? $shipment->current_status
            : ShipmentStatus::fromString((string) $shipment->current_status);

        $allowedNext = $this->lifecycleService->allowedNextStatuses($currentStatus);

        return array_map(function (ShipmentStatus $status) {
            return [
                'value' => $status->value,
                'label' => $status->label(),
            ];
        }, $allowedNext);
    }
}
