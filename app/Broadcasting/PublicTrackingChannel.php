<?php

namespace App\Broadcasting;

use App\Models\Shipment;

class PublicTrackingChannel
{
    /**
     * Authenticate access to the public tracking channel.
     * This channel is publicly accessible for tracking purposes.
     */
    public function join(Shipment $shipment): array|bool
    {
        // Anyone can join public tracking channels
        return true;
    }
}