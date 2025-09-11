<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['hq_admin', 'branch_ops_manager', 'branch_attendant', 'support', 'finance']);
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user, User $customer): bool
    {
        // HQ Admin can view all customers
        if ($user->hasRole('hq_admin')) {
            return true;
        }

        // Branch users can view customers linked to their shipments
        if ($this->isBranchUser($user)) {
            return $customer->shipments()
                ->where(function ($query) use ($user) {
                    $query->where('origin_branch_id', $user->hub_id)
                          ->orWhere('dest_branch_id', $user->hub_id);
                })
                ->exists() ||
                $customer->hub_id === $user->hub_id; // Created by user's branch
        }

        return false;
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['hq_admin', 'branch_ops_manager', 'branch_attendant', 'support']);
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user, User $customer): bool
    {
        // HQ Admin can update all customers
        if ($user->hasRole('hq_admin')) {
            return true;
        }

        // Branch ops can update customers from their branch
        if ($this->isBranchOps($user)) {
            return $customer->hub_id === $user->hub_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user, User $customer): bool
    {
        // Only HQ Admin can delete customers (soft delete)
        return $user->hasRole('hq_admin');
    }

    /**
     * Determine whether the user can restore the customer.
     */
    public function restore(User $user, User $customer): bool
    {
        return $user->hasRole('hq_admin');
    }

    /**
     * Determine whether the user can permanently delete the customer.
     */
    public function forceDelete(User $user, User $customer): bool
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