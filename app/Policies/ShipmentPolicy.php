<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any shipments.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['hq_admin', 'branch_ops_manager', 'branch_attendant', 'support', 'finance']);
    }

    /**
     * Determine whether the user can view the shipment.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        // HQ Admin can view all shipments
        if ($user->hasRole('hq_admin')) {
            return true;
        }

        // Branch users can view shipments from their branches
        if ($this->isBranchUser($user)) {
            return $shipment->origin_branch_id === $user->hub_id ||
                   $shipment->dest_branch_id === $user->hub_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create shipments.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['hq_admin', 'branch_ops_manager', 'branch_attendant']);
    }

    /**
     * Determine whether the user can update the shipment.
     */
    public function update(User $user, Shipment $shipment): bool
    {
        // HQ Admin can update all shipments
        if ($user->hasRole('hq_admin')) {
            return true;
        }

        // Branch ops can update shipments from their branch
        if ($this->isBranchOps($user)) {
            return $shipment->origin_branch_id === $user->hub_id ||
                   $shipment->dest_branch_id === $user->hub_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the shipment.
     */
    public function delete(User $user, Shipment $shipment): bool
    {
        // Only HQ Admin can delete shipments (soft delete)
        return $user->hasRole('hq_admin');
    }

    /**
     * Determine whether the user can restore the shipment.
     */
    public function restore(User $user, Shipment $shipment): bool
    {
        return $user->hasRole('hq_admin');
    }

    /**
     * Determine whether the user can permanently delete the shipment.
     */
    public function forceDelete(User $user, Shipment $shipment): bool
    {
        return $user->hasRole('hq_admin');
    }

    // Helper methods
    private function isBranchUser(User $user): bool
    {
        return !is_null($user->hub_id) && (
            $user->hasRole('branch_ops_manager') ||
            $user->hasRole('branch_attendant') ||
            $user->hasRole('driver') ||
            $user->hasRole('finance') ||
            $user->hasRole('support')
        );
    }

    private function isBranchOps(User $user): bool
    {
        return !is_null($user->hub_id) && (
            $user->hasRole('branch_ops_manager') ||
            $user->hasRole('branch_attendant')
        );
    }
}