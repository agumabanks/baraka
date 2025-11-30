<?php

namespace App\Services\Dispatch;

use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\Shipment;

class AssignmentEngine
{
    /**
    * Suggest the best worker for a shipment within a branch.
    * Current heuristic: pick active worker with lowest active shipment count.
    */
    public function suggestWorker(Shipment $shipment, Branch $branch): ?BranchWorker
    {
        $workers = BranchWorker::query()
            ->where('branch_id', $branch->id)
            ->active()
            ->withCount(['assignedShipments' => function ($q) {
                $q->whereNull('delivered_at');
            }])
            ->orderBy('assigned_shipments_count')
            ->orderBy('id')
            ->get();

        return $workers->first();
    }

    /**
    * Assign the suggested worker, returning the worker or null if none available.
    */
    public function autoAssign(Shipment $shipment, Branch $branch): ?BranchWorker
    {
        $worker = $this->suggestWorker($shipment, $branch);

        if (! $worker) {
            return null;
        }

        $shipment->assigned_worker_id = $worker->id;
        $shipment->assigned_at = now();
        $shipment->save();

        return $worker;
    }
}
