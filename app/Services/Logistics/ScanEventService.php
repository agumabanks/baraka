<?php

namespace App\Services\Logistics;

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Models\Backend\Parcel;
use App\Models\Bag;
use App\Models\ScanEvent;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScanEventService
{
    public function __construct(private ShipmentLifecycleService $lifecycleService)
    {
    }

    /**
     * Record a scan event from handhelds/mobile clients and cascade lifecycle updates.
     *
     * @throws Throwable
     */
    public function record(array $attributes): ScanEvent
    {
        $scanType = $this->resolveScanType($attributes['type'] ?? null);

        if (! $scanType) {
            throw new \InvalidArgumentException('Unknown scan type provided.');
        }

        $occurredAt = $attributes['occurred_at'] ?? now();

        [$shipment, $bag] = $this->resolveEntities($attributes);

        return DB::transaction(function () use ($attributes, $scanType, $occurredAt, $shipment, $bag) {
            $scanEvent = ScanEvent::create([
                'sscc' => $attributes['sscc'] ?? ($bag?->code),
                'shipment_id' => $shipment?->id,
                'bag_id' => $bag?->id,
                'route_id' => $attributes['route_id'] ?? null,
                'stop_id' => $attributes['stop_id'] ?? null,
                'type' => $scanType,
                'status_after' => null,
                'branch_id' => $attributes['branch_id'] ?? null,
                'leg_id' => $attributes['leg_id'] ?? null,
                'user_id' => $attributes['user_id'] ?? null,
                'location_type' => $attributes['location_type'] ?? null,
                'location_id' => $attributes['location_id'] ?? null,
                'occurred_at' => $occurredAt,
                'geojson' => $attributes['geojson'] ?? null,
                'note' => $attributes['note'] ?? null,
                'payload' => $attributes['payload'] ?? null,
            ]);

            if ($shipment && ($status = $this->resolveTargetStatus($scanType, $attributes))) {
                $transition = $this->lifecycleService->transition($shipment, $status, [
                    'trigger' => 'scan_event',
                    'scan_event' => $scanEvent,
                    'performed_by' => $attributes['user_id'] ?? null,
                    'timestamp' => $occurredAt,
                    'location_type' => $attributes['location_type'] ?? null,
                    'location_id' => $attributes['location_id'] ?? null,
                    'metadata' => $attributes['metadata'] ?? null,
                ]);

                $scanEvent->status_after = ShipmentStatus::fromString($transition->to_status);
                $scanEvent->save();
            }

            return $scanEvent->refresh();
        });
    }

    private function resolveScanType(?string $type): ?ScanType
    {
        if (! $type) {
            return null;
        }

        return ScanType::fromString($type);
    }

    private function resolveTargetStatus(ScanType $scanType, array $attributes): ?ShipmentStatus
    {
        if (! empty($attributes['status_after'])) {
            return ShipmentStatus::fromString($attributes['status_after']);
        }

        return $scanType->resultingStatus();
    }

    private function resolveEntities(array $attributes): array
    {
        $shipment = null;
        $bag = null;

        if (! empty($attributes['shipment_id'])) {
            $shipment = Shipment::find($attributes['shipment_id']);
        }

        if (! $shipment && ! empty($attributes['tracking_number'])) {
            $shipment = Shipment::where('tracking_number', $attributes['tracking_number'])->first();
        }

        if (! $shipment && ! empty($attributes['sscc'])) {
            $parcel = Parcel::where('sscc', $attributes['sscc'])->first();
            $shipment = $parcel?->shipment;
        }

        if (! empty($attributes['bag_id'])) {
            $bag = Bag::find($attributes['bag_id']);
        }

        if (! $bag && ! empty($attributes['bag_code'])) {
            $bag = Bag::where('code', $attributes['bag_code'])->first();
        }

        return [$shipment, $bag];
    }
}
