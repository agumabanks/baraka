<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Geofence extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'entity_type',
        'entity_id',
        'center_latitude',
        'center_longitude',
        'radius_meters',
        'polygon_coordinates',
        'is_active',
        'alert_on_enter',
        'alert_on_exit',
        'require_scan_within',
        'metadata',
    ];

    protected $casts = [
        'center_latitude' => 'decimal:8',
        'center_longitude' => 'decimal:8',
        'radius_meters' => 'decimal:2',
        'polygon_coordinates' => 'array',
        'is_active' => 'boolean',
        'require_scan_within' => 'boolean',
        'metadata' => 'array',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }

    public function scopeCircle($query)
    {
        return $query->where('type', 'circle');
    }

    public function scopePolygon($query)
    {
        return $query->where('type', 'polygon');
    }

    /**
     * Check if a point is within this geofence
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        if ($this->type === 'circle') {
            return $this->containsPointCircle($latitude, $longitude);
        }

        return $this->containsPointPolygon($latitude, $longitude);
    }

    /**
     * Check if point is within circle geofence using Haversine formula
     */
    protected function containsPointCircle(float $latitude, float $longitude): bool
    {
        $distance = $this->calculateDistance(
            $this->center_latitude,
            $this->center_longitude,
            $latitude,
            $longitude
        );

        return $distance <= $this->radius_meters;
    }

    /**
     * Check if point is within polygon geofence using ray casting algorithm
     */
    protected function containsPointPolygon(float $latitude, float $longitude): bool
    {
        $polygon = $this->polygon_coordinates ?? [];
        
        if (count($polygon) < 3) {
            return false;
        }

        $inside = false;
        $n = count($polygon);
        
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            if ((($yi > $longitude) !== ($yj > $longitude)) &&
                ($latitude < ($xj - $xi) * ($longitude - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get distance from geofence center (for circle type)
     */
    public function getDistanceFromCenter(float $latitude, float $longitude): float
    {
        return $this->calculateDistance(
            $this->center_latitude,
            $this->center_longitude,
            $latitude,
            $longitude
        );
    }

    /**
     * Create geofence for a branch
     */
    public static function createForBranch($branch, float $radiusMeters = 200): self
    {
        return static::create([
            'name' => "Branch: {$branch->name}",
            'type' => 'circle',
            'entity_type' => 'branch',
            'entity_id' => $branch->id,
            'center_latitude' => $branch->latitude,
            'center_longitude' => $branch->longitude,
            'radius_meters' => $radiusMeters,
            'is_active' => true,
            'alert_on_enter' => 'log',
            'alert_on_exit' => 'log',
            'require_scan_within' => true,
        ]);
    }

    /**
     * Create geofence for a hub
     */
    public static function createForHub($hub, float $radiusMeters = 500): self
    {
        return static::create([
            'name' => "Hub: {$hub->name}",
            'type' => 'circle',
            'entity_type' => 'hub',
            'entity_id' => $hub->id,
            'center_latitude' => $hub->latitude,
            'center_longitude' => $hub->longitude,
            'radius_meters' => $radiusMeters,
            'is_active' => true,
            'alert_on_enter' => 'log',
            'alert_on_exit' => 'log',
            'require_scan_within' => false,
        ]);
    }
}
