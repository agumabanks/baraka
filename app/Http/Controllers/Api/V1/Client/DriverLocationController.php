<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Events\DriverLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDriverLocationRequest;
use App\Models\DeliveryMan;
use App\Models\DriverLocation;
use App\Traits\ApiReturnFormatTrait;

/**
 * @OA\Tag(
 *     name="Driver Locations",
 *     description="API Endpoints for driver location tracking"
 * )
 */
class DriverLocationController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/driver/locations",
     *     summary="Store driver locations",
     *     description="Store batch of driver location updates",
     *     operationId="storeDriverLocations",
     *     tags={"Driver Locations"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"locations"},
     *
     *             @OA\Property(property="locations", type="array", @OA\Items(
     *                 @OA\Property(property="latitude", type="number", format="float", description="Latitude coordinate"),
     *                 @OA\Property(property="longitude", type="number", format="float", description="Longitude coordinate"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", description="Location timestamp")
     *             ))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Locations stored successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Locations stored")
     *         )
     *     )
     * )
     */
    public function store(StoreDriverLocationRequest $request)
    {
        $driver = DeliveryMan::where('user_id', auth()->id())->firstOrFail();

        // Performance optimization: Batch insert locations
        $locationsData = [];
        foreach ($request->locations as $location) {
            $locationsData[] = [
                'driver_id' => $driver->id,
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'timestamp' => $location['timestamp'] ?? now(),
                'accuracy' => $location['accuracy'] ?? null,
                'speed' => $location['speed'] ?? null,
                'heading' => $location['heading'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert for better performance
        DriverLocation::insert($locationsData);

        // Keep only the latest 1000 locations per driver for performance
        $driver->locations()
            ->orderBy('timestamp', 'desc')
            ->skip(1000)
            ->delete();

        // Broadcast location update
        broadcast(new DriverLocationUpdated($driver, $request->locations));

        return $this->responseWithSuccess('Locations stored', [], 201);
    }
}
