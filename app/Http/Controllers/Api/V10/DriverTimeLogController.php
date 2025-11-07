<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\DriverTimeLogType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V10\Driver\StoreDriverTimeLogRequest;
use App\Models\DriverRoster;
use App\Models\DriverTimeLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTimeLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DriverTimeLog::class);

        $query = DriverTimeLog::with(['driver', 'roster']);

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        if ($request->filled('log_type')) {
            $type = DriverTimeLogType::fromString($request->input('log_type'));
            $query->where('log_type', $type->value);
        }

        $logs = $query->latest('logged_at')->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function store(StoreDriverTimeLogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['logged_at'] = $data['logged_at'] ?? now();

        if (empty($data['roster_id'])) {
            $data['roster_id'] = $this->resolveActiveRosterId($data['driver_id'], $data['logged_at']);
        }

        $log = DriverTimeLog::create($data);

        return response()->json([
            'success' => true,
            'data' => $log->fresh()->load(['driver', 'roster']),
            'message' => 'Time log recorded',
        ], 201);
    }

    protected function resolveActiveRosterId(int $driverId, string $loggedAt): ?int
    {
        $timestamp = Carbon::parse($loggedAt);

        return DriverRoster::where('driver_id', $driverId)
            ->where('start_time', '<=', $timestamp)
            ->where('end_time', '>=', $timestamp)
            ->value('id');
    }
}
