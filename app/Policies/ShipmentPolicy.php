<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    /**
     * Determine whether the user can view the shipment.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        // Users can view their own shipments
        if ($shipment->customer_id === $user->id) {
            return true;
        }

        // Admins can view all shipments
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create shipments.
     */
    public function create(User $user): bool
    {
        // Only merchants and customers can create shipments
        return in_array($user->user_type, ['merchant', 'customer']);
    }

    /**
     * Determine whether the user can cancel the shipment.
     */
    public function cancel(User $user, Shipment $shipment): bool
    {
        // Users can cancel their own shipments if not yet processed
        if ($shipment->customer_id === $user->id) {
            return in_array($shipment->current_status, ['created', 'handed_over']);
        }

        // Admins can cancel any shipment
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the shipment status.
     */
    public function updateStatus(User $user, Shipment $shipment): bool
    {
        // Only admins can update shipment status
        return $user->hasRole('admin');
    }
}
