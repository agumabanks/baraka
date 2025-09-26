<?php

namespace App\Events;

use App\Models\Shipment;
use App\Models\ScanEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Shipment $shipment,
        public ?ScanEvent $scanEvent = null
    ) {
    }
}

