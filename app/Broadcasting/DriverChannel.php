<?php

namespace App\Broadcasting;

use App\Models\User;

class DriverChannel
{
    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, int $driverId): array|bool
    {
        // Users can only join their own driver channel
        if ($user->user_type === 'deliveryman' && $user->deliveryman && $user->deliveryman->id === $driverId) {
            return true;
        }

        // Admins can join any driver channel
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }
}