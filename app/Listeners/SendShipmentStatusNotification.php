<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Notifications\ShipmentStatusChangedNotification;

class SendShipmentStatusNotification
{
    public function handle(ShipmentStatusChanged $event): void
    {
        $shipment = $event->shipment->loadMissing(['customer','originBranch','destBranch']);
        $customer = $shipment->customer;
        if (!$customer) {
            return;
        }

        // Respect user notification preferences if set
        $prefs = $customer->notification_prefs ?? [];
        if (is_array($prefs) && ($prefs['shipment_status_email'] ?? true) === false) {
            return;
        }

        $customer->notify(new ShipmentStatusChangedNotification($shipment));
    }
}

