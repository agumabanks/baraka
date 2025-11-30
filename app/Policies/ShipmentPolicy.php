<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use App\Services\BranchContext;

class ShipmentPolicy
{
    /**
     * Determine whether the user can view the shipment.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $branchId = BranchContext::currentId() ?? $user->primary_branch_id;

        if ($branchId && ($shipment->origin_branch_id === $branchId || $shipment->dest_branch_id === $branchId)) {
            return true;
        }

        return $shipment->customer_id === $user->id;
    }

    /**
     * Determine whether the user can create shipments.
     */
    public function create(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        // Branch users can create within their active branch
        if ($user->primary_branch_id || BranchContext::currentId()) {
            return true;
        }

        return $user->isClient();
    }

    /**
     * Determine whether the user can cancel the shipment.
     */
    public function cancel(User $user, Shipment $shipment): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        // Users can cancel their own shipments if not yet processed
        if ($shipment->customer_id === $user->id) {
            return in_array($shipment->current_status, ['created', 'handed_over']);
        }

        return false;
    }

    /**
     * Determine whether the user can update the shipment status.
     */
    public function updateStatus(User $user, Shipment $shipment): bool
    {
        if ($this->isAdmin($user) || $user->hasPermission('branch_manage')) {
            return true;
        }

        $branchId = BranchContext::currentId() ?? $user->primary_branch_id;

        return $branchId && ($shipment->origin_branch_id === $branchId || $shipment->dest_branch_id === $branchId);
    }

    protected function isAdmin(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'operations_admin']);
    }
}
