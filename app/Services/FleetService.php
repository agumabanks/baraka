<?php

namespace App\Services;

use App\Models\Backend\Vehicle;
use App\Models\Driver;
use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Shipment;
use App\Models\Consolidation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class FleetService
{
    /**
     * Create a new manifest
     */
    public function createManifest(array $data, User $creator): Manifest
    {
        return DB::transaction(function () use ($data, $creator) {
            $manifest = Manifest::create([
                'number' => 'MAN-' . strtoupper(uniqid()),
                'mode' => $data['mode'],
                'type' => $data['type'] ?? 'INTERNAL',
                'origin_branch_id' => $creator->branch_id,
                'destination_branch_id' => $data['destination_branch_id'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'departure_at' => $data['departure_at'] ?? now(),
                'status' => Manifest::STATUS_OPEN,
            ]);

            if (!empty($data['items'])) {
                $this->addItemsToManifest($manifest, $data['items']);
            }

            return $manifest;
        });
    }

    /**
     * Add items (shipments/consolidations) to manifest
     */
    public function addItemsToManifest(Manifest $manifest, array $items): void
    {
        foreach ($items as $item) {
            // $item format: ['id' => 1, 'type' => 'shipment']
            $modelClass = $item['type'] === 'consolidation' ? Consolidation::class : Shipment::class;
            
            ManifestItem::firstOrCreate([
                'manifest_id' => $manifest->id,
                'manifestable_id' => $item['id'],
                'manifestable_type' => $modelClass,
            ], [
                'status' => 'LOADED',
                'loaded_at' => now(),
            ]);

            // Update item status if needed (e.g. Shipment -> BAGGED or LINEHAUL_DEPARTED)
            // This logic might belong in a listener or specific service method
        }
    }

    /**
     * Assign driver and vehicle to manifest
     */
    public function assignResources(Manifest $manifest, ?Driver $driver, ?Vehicle $vehicle): void
    {
        if ($vehicle && $vehicle->status !== 'ACTIVE') {
            throw new Exception("Vehicle {$vehicle->plate_no} is not active.");
        }

        if ($driver && $driver->status->value !== 'ACTIVE') {
            throw new Exception("Driver {$driver->name} is not active.");
        }

        $manifest->update([
            'driver_id' => $driver?->id,
            'vehicle_id' => $vehicle?->id,
        ]);
    }

    /**
     * Dispatch manifest (start trip)
     */
    public function dispatchManifest(Manifest $manifest): void
    {
        if ($manifest->status !== Manifest::STATUS_OPEN) {
            throw new Exception("Manifest is not open.");
        }

        if (!$manifest->driver_id || !$manifest->vehicle_id) {
            throw new Exception("Driver and Vehicle must be assigned before dispatch.");
        }

        DB::transaction(function () use ($manifest) {
            $manifest->update([
                'status' => Manifest::STATUS_DEPARTED,
                'departure_at' => now(),
            ]);

            // Update vehicle location
            if ($manifest->vehicle) {
                $manifest->vehicle->update([
                    'status' => 'IN_TRANSIT',
                    'last_location_update' => now(),
                ]);
            }

            // Update items status
            foreach ($manifest->items as $item) {
                // Logic to update shipment/consolidation status to 'IN_TRANSIT' or similar
            }
        });
    }

    /**
     * Arrive manifest at destination
     */
    public function arriveManifest(Manifest $manifest, User $receiver): void
    {
        if ($manifest->status !== Manifest::STATUS_DEPARTED) {
            throw new Exception("Manifest has not departed.");
        }

        DB::transaction(function () use ($manifest, $receiver) {
            $manifest->update([
                'status' => Manifest::STATUS_ARRIVED,
                'arrival_at' => now(),
            ]);

            // Update vehicle location
            if ($manifest->vehicle) {
                $manifest->vehicle->update([
                    'status' => 'ACTIVE',
                    'current_branch_id' => $receiver->branch_id,
                    'last_location_update' => now(),
                ]);
            }
        });
    }
}
