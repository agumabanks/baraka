<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class TaskPolicy
{
    public function pod(User $user, Shipment $shipment): bool
    {
        // Allow drivers or assigned users to submit POD
        return $user->id === $shipment->assigned_driver_id || $user->hasRole('driver');
    }
}
