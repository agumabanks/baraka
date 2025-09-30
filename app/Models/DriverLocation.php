<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    protected $fillable = [
        'driver_id',
        'latitude',
        'longitude',
        'timestamp',
        'accuracy',
        'speed',
        'heading',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'timestamp' => 'datetime',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
    ];

    /**
     * Get the driver that this location belongs to.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'driver_id');
    }

    /**
     * Get the latest location for a driver.
     */
    public static function getLatestForDriver(int $driverId)
    {
        return static::where('driver_id', $driverId)
            ->latest('timestamp')
            ->first();
    }

    /**
     * Get location history for a driver within a time range.
     */
    public static function getHistoryForDriver(int $driverId, $startTime, $endTime = null)
    {
        $query = static::where('driver_id', $driverId)
            ->where('timestamp', '>=', $startTime);

        if ($endTime) {
            $query->where('timestamp', '<=', $endTime);
        }

        return $query->orderBy('timestamp', 'asc')->get();
    }
}