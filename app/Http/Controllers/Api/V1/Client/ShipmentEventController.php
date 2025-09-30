<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Events\ShipmentEventCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreShipmentEventRequest;
use App\Models\ScanEvent;
use App\Models\Shipment;
use App\Traits\ApiReturnFormatTrait;

class ShipmentEventController extends Controller
{
    use ApiReturnFormatTrait;

    public function store(StoreShipmentEventRequest $request, Shipment $shipment)
    {
        $this->authorize('update', $shipment);

        $event = ScanEvent::create([
            'shipment_id' => $shipment->id,
            'type' => $request->type,
            'occurred_at' => $request->occurred_at ?? now(),
            'location' => $request->location,
            'notes' => $request->notes,
        ]);

        // Update shipment status based on event
        $shipment->updateStatusFromScan($event);

        // Broadcast event
        broadcast(new ShipmentEventCreated($event));

        return $this->responseWithSuccess('Event created', [
            'event' => $event,
        ], 201);
    }
}
