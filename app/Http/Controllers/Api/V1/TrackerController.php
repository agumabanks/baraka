<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\TrackerEvent;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrackerController extends Controller
{
    use ApiReturnFormatTrait;

    public function ingest(Request $request)
    {
        $data = $request->validate([
            'shipment_public_token' => 'nullable|string',
            'tracking_number' => 'nullable|string',
            'tracker_id' => 'nullable|string|max:120',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'temperature_c' => 'nullable|numeric',
            'battery_percent' => 'nullable|integer|min:0|max:100',
            'recorded_at' => 'nullable|date',
            'payload' => 'nullable|array',
        ]);

        $shipment = null;

        if (! empty($data['shipment_public_token'])) {
            $shipment = Shipment::where('public_token', $data['shipment_public_token'])->first();
        }

        if (! $shipment && ! empty($data['tracking_number'])) {
            $shipment = Shipment::where('tracking_number', $data['tracking_number'])->first();
        }

        if (! $shipment) {
            throw ValidationException::withMessages([
                'tracking' => ['Shipment not found for provided token/number'],
            ]);
        }

        if (isset($data['branch_id']) && ! in_array($data['branch_id'], [$shipment->origin_branch_id, $shipment->dest_branch_id], true)) {
            return $this->responseWithError('Branch mismatch for tracker event', [], 403);
        }

        $event = TrackerEvent::create([
            'shipment_id' => $shipment->id,
            'branch_id' => $data['branch_id'] ?? $shipment->origin_branch_id,
            'tracker_id' => $data['tracker_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'temperature_c' => $data['temperature_c'] ?? null,
            'battery_percent' => $data['battery_percent'] ?? null,
            'payload' => $data['payload'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        $metadata = $shipment->metadata ?? [];
        $metadata['last_tracker'] = [
            'tracker_id' => $event->tracker_id,
            'battery_percent' => $event->battery_percent,
            'temperature_c' => $event->temperature_c,
            'recorded_at' => $event->recorded_at,
        ];
        $shipment->metadata = $metadata;
        $shipment->save();

        return $this->responseWithSuccess('Tracker event stored', [
            'event_id' => $event->id,
        ]);
    }
}
