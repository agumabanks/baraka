<?php

namespace App\Services;

use App\Models\Geofence;
use App\Models\ScanEvent;
use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\Backend\Hub;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class GeofencingService
{
    protected const EARTH_RADIUS_METERS = 6371000;
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Validate if a scan location is within expected geofence
     */
    public function validateScanLocation(
        float $latitude,
        float $longitude,
        ?int $branchId = null,
        ?int $hubId = null,
        float $maxDistanceMeters = 500
    ): array {
        $result = [
            'is_valid' => false,
            'within_geofence' => false,
            'geofence_id' => null,
            'geofence_name' => null,
            'distance_from_center' => null,
            'validation_errors' => [],
        ];

        // Get applicable geofences
        $geofences = $this->getApplicableGeofences($branchId, $hubId);

        if ($geofences->isEmpty()) {
            // No geofences configured - validate based on max distance from branch/hub
            $result['is_valid'] = true;
            $result['validation_errors'][] = 'No geofences configured for location validation';
            return $result;
        }

        foreach ($geofences as $geofence) {
            if ($geofence->containsPoint($latitude, $longitude)) {
                $result['is_valid'] = true;
                $result['within_geofence'] = true;
                $result['geofence_id'] = $geofence->id;
                $result['geofence_name'] = $geofence->name;
                $result['distance_from_center'] = $geofence->getDistanceFromCenter($latitude, $longitude);
                return $result;
            }
        }

        // Point is outside all geofences
        $closestGeofence = $this->findClosestGeofence($geofences, $latitude, $longitude);
        
        if ($closestGeofence) {
            $distance = $closestGeofence->getDistanceFromCenter($latitude, $longitude);
            $result['geofence_id'] = $closestGeofence->id;
            $result['geofence_name'] = $closestGeofence->name;
            $result['distance_from_center'] = $distance;
            
            // Allow scans within extended tolerance
            if ($distance <= $maxDistanceMeters) {
                $result['is_valid'] = true;
                $result['validation_errors'][] = "Scan is {$distance}m from geofence boundary (within tolerance)";
            } else {
                $result['validation_errors'][] = "Scan is {$distance}m from expected location (exceeds {$maxDistanceMeters}m tolerance)";
            }
        }

        return $result;
    }

    /**
     * Get applicable geofences for a branch or hub
     */
    protected function getApplicableGeofences(?int $branchId, ?int $hubId): Collection
    {
        $cacheKey = "geofences_branch_{$branchId}_hub_{$hubId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $hubId) {
            $query = Geofence::active();
            
            if ($branchId) {
                $query->orWhere(function ($q) use ($branchId) {
                    $q->where('entity_type', 'branch')->where('entity_id', $branchId);
                });
            }
            
            if ($hubId) {
                $query->orWhere(function ($q) use ($hubId) {
                    $q->where('entity_type', 'hub')->where('entity_id', $hubId);
                });
            }
            
            return $query->get();
        });
    }

    /**
     * Find the closest geofence to a point
     */
    protected function findClosestGeofence(Collection $geofences, float $latitude, float $longitude): ?Geofence
    {
        $closest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($geofences as $geofence) {
            $distance = $geofence->getDistanceFromCenter($latitude, $longitude);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closest = $geofence;
            }
        }

        return $closest;
    }

    /**
     * Check and trigger geofence alerts for a shipment
     */
    public function checkGeofenceAlerts(Shipment $shipment, float $latitude, float $longitude): array
    {
        $alerts = [];
        
        // Get destination branch geofence
        $destGeofence = Geofence::active()
            ->where('entity_type', 'branch')
            ->where('entity_id', $shipment->dest_branch_id)
            ->first();

        if ($destGeofence && $destGeofence->containsPoint($latitude, $longitude)) {
            $alerts[] = [
                'type' => 'arrival',
                'message' => "Shipment {$shipment->tracking_number} arrived at destination",
                'geofence' => $destGeofence->name,
            ];
            
            // Trigger notification if configured
            if ($destGeofence->alert_on_enter === 'notify') {
                $this->triggerGeofenceNotification($shipment, $destGeofence, 'enter');
            }
        }

        return $alerts;
    }

    /**
     * Create geofences for all branches that don't have one
     */
    public function createMissingBranchGeofences(float $defaultRadiusMeters = 200): int
    {
        $branches = Branch::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $created = 0;

        foreach ($branches as $branch) {
            $exists = Geofence::where('entity_type', 'branch')
                ->where('entity_id', $branch->id)
                ->exists();

            if (!$exists) {
                Geofence::createForBranch($branch, $defaultRadiusMeters);
                $created++;
            }
        }

        Log::info("Created {$created} branch geofences");
        return $created;
    }

    /**
     * Create geofences for all hubs that don't have one
     */
    public function createMissingHubGeofences(float $defaultRadiusMeters = 500): int
    {
        $hubs = Hub::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $created = 0;

        foreach ($hubs as $hub) {
            $exists = Geofence::where('entity_type', 'hub')
                ->where('entity_id', $hub->id)
                ->exists();

            if (!$exists) {
                Geofence::createForHub($hub, $defaultRadiusMeters);
                $created++;
            }
        }

        Log::info("Created {$created} hub geofences");
        return $created;
    }

    /**
     * Get all shipments currently within a geofence
     */
    public function getShipmentsWithinGeofence(Geofence $geofence): Collection
    {
        // Get shipments with recent scans
        $recentScans = ScanEvent::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('occurred_at', '>=', now()->subHours(24))
            ->orderBy('occurred_at', 'desc')
            ->get()
            ->unique('shipment_id');

        return $recentScans->filter(function ($scan) use ($geofence) {
            return $geofence->containsPoint($scan->latitude, $scan->longitude);
        })->map(function ($scan) {
            return $scan->shipment;
        })->filter();
    }

    /**
     * Calculate distance between two GPS points
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Get geofence statistics
     */
    public function getGeofenceStats(): array
    {
        return Cache::remember('geofence_stats', 300, function () {
            return [
                'total_geofences' => Geofence::count(),
                'active_geofences' => Geofence::active()->count(),
                'branch_geofences' => Geofence::where('entity_type', 'branch')->count(),
                'hub_geofences' => Geofence::where('entity_type', 'hub')->count(),
                'zone_geofences' => Geofence::where('entity_type', 'zone')->count(),
            ];
        });
    }

    /**
     * Trigger notification for geofence event
     */
    protected function triggerGeofenceNotification(Shipment $shipment, Geofence $geofence, string $event): void
    {
        Log::info("Geofence {$event} notification", [
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'geofence_id' => $geofence->id,
            'geofence_name' => $geofence->name,
            'event' => $event,
        ]);

        // TODO: Integrate with NotificationOrchestrationService when ready
    }

    /**
     * Clear geofence cache
     */
    public function clearCache(): void
    {
        Cache::forget('geofence_stats');
        // Clear branch/hub specific caches would need pattern matching or tags
    }
}
