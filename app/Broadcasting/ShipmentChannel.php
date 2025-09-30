<?php

namespace App\Broadcasting;

use App\Models\Shipment;
use App\Models\User;

class ShipmentChannel
{
    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, Shipment $shipment): array|bool
    {
        // Users can join their own shipment channels
        if ($shipment->customer_id === $user->id) {
            return true;
        }

        // Admins can join any shipment channel
        if ($user->hasRole('admin')) {
            return true;
        }

        // Drivers assigned to the shipment can join
        if ($shipment->driver && $shipment->driver->user_id === $user->id) {
            return true;
        }

        return false;
    }
}