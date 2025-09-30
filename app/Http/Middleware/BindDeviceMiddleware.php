<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BindDeviceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $deviceUuid = $request->header('device_uuid');
        if (! $deviceUuid) {
            return response()->json(['error' => 'device_uuid header required'], 400);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $device = Device::updateOrCreate(
            ['device_uuid' => $deviceUuid],
            [
                'user_id' => $user->id,
                'platform' => $request->header('platform', 'unknown'),
                'push_token' => $request->header('push_token'),
                'last_seen_at' => now(),
            ]
        );

        // Optionally attach device to request
        $request->merge(['device' => $device]);

        return $next($request);
    }
}
