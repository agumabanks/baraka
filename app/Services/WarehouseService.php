<?php

namespace App\Services;

use App\Models\Backend\Branch;
use App\Models\Parcel;
use App\Models\User;
use App\Models\WarehouseMovement;
use App\Models\WhLocation;
use Illuminate\Support\Facades\DB;
use Exception;

class WarehouseService
{
    /**
     * Move a parcel to a new location
     */
    public function moveParcel(Parcel $parcel, WhLocation $toLocation, User $user, ?string $notes = null): WarehouseMovement
    {
        // Validation
        if ($toLocation->branch_id !== $user->branch_id && !$user->hasRole('super-admin')) {
            throw new Exception("Cannot move parcel to a location in another branch.");
        }

        if ($toLocation->capacity && $toLocation->parcels()->count() >= $toLocation->capacity) {
            throw new Exception("Location {$toLocation->code} is at full capacity.");
        }

        return DB::transaction(function () use ($parcel, $toLocation, $user, $notes) {
            $fromLocationId = $parcel->current_location_id;

            // Update parcel location
            $parcel->update(['current_location_id' => $toLocation->id]);

            // Log movement
            return WarehouseMovement::create([
                'parcel_id' => $parcel->id,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocation->id,
                'user_id' => $user->id,
                'type' => 'MOVE',
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Putaway a parcel (initial placement)
     */
    public function putawayParcel(Parcel $parcel, WhLocation $toLocation, User $user): WarehouseMovement
    {
        return $this->moveParcel($parcel, $toLocation, $user, 'Initial putaway');
    }

    /**
     * Pick a parcel (remove from location)
     */
    public function pickParcel(Parcel $parcel, User $user): WarehouseMovement
    {
        return DB::transaction(function () use ($parcel, $user) {
            $fromLocationId = $parcel->current_location_id;

            if (!$fromLocationId) {
                throw new Exception("Parcel is not in any location.");
            }

            $parcel->update(['current_location_id' => null]);

            return WarehouseMovement::create([
                'parcel_id' => $parcel->id,
                'from_location_id' => $fromLocationId,
                'to_location_id' => null,
                'user_id' => $user->id,
                'type' => 'PICK',
            ]);
        });
    }

    /**
     * Get inventory for a location (recursive)
     */
    public function getInventory(WhLocation $location, bool $recursive = false)
    {
        if (!$recursive) {
            return $location->parcels()->with('shipment')->get();
        }

        // Get all descendant location IDs
        $descendantIds = $this->getDescendantIds($location);
        $descendantIds[] = $location->id;

        return Parcel::whereIn('current_location_id', $descendantIds)
            ->with(['shipment', 'currentLocation'])
            ->get();
    }

    protected function getDescendantIds(WhLocation $location)
    {
        $ids = [];
        foreach ($location->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }
        return $ids;
    }
}
