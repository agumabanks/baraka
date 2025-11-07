<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\RosterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V10\Driver\StoreDriverRosterRequest;
use App\Http\Requests\Api\V10\Driver\UpdateDriverRosterRequest;
use App\Models\DriverRoster;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverRosterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DriverRoster::class);

        $query = DriverRoster::with(['driver', 'branch']);

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('status')) {
            $status = RosterStatus::fromString($request->input('status'));
            $query->where('status', $status->value);
        }

        if ($request->filled('from')) {
            $query->whereDate('start_time', '>=', Carbon::parse($request->input('from')));
        }

        if ($request->filled('to')) {
            $query->whereDate('end_time', '<=', Carbon::parse($request->input('to')));
        }

        $rosters = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $rosters,
        ]);
    }

    public function store(StoreDriverRosterRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($this->hasOverlap($data['driver_id'], $data['start_time'], $data['end_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Roster overlaps with an existing shift for this driver.',
            ], 422);
        }

        $roster = DriverRoster::create(array_merge($data, [
            'status' => $data['status'] ?? RosterStatus::SCHEDULED->value,
            'planned_hours' => $data['planned_hours'] ?? $this->computePlannedHours($data['start_time'], $data['end_time']),
        ]));

        return response()->json([
            'success' => true,
            'data' => $roster->fresh()->load(['driver', 'branch']),
            'message' => 'Driver roster created',
        ], 201);
    }

    public function update(UpdateDriverRosterRequest $request, DriverRoster $roster): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['start_time']) || isset($data['end_time'])) {
            $start = $data['start_time'] ?? $roster->start_time;
            $end = $data['end_time'] ?? $roster->end_time;

            if ($this->hasOverlap($roster->driver_id, $start, $end, $roster->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Roster overlaps with an existing shift for this driver.',
                ], 422);
            }

            if (! isset($data['planned_hours'])) {
                $data['planned_hours'] = $this->computePlannedHours($start, $end);
            }
        }

        if (isset($data['status'])) {
            $data['status'] = RosterStatus::fromString($data['status'])->value;
        }

        $roster->fill($data)->save();

        return response()->json([
            'success' => true,
            'data' => $roster->fresh()->load(['driver', 'branch']),
            'message' => 'Driver roster updated',
        ]);
    }

    public function destroy(Request $request, DriverRoster $roster): JsonResponse
    {
        $this->authorize('delete', $roster);

        $roster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Driver roster deleted',
        ]);
    }

    protected function hasOverlap(int $driverId, string $start, string $end, ?int $ignoreId = null): bool
    {
        $startTime = Carbon::parse($start);
        $endTime = Carbon::parse($end);

        return DriverRoster::where('driver_id', $driverId)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($subQuery) use ($startTime, $endTime) {
                        $subQuery->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })->exists();
    }

    protected function computePlannedHours(string $start, string $end): int
    {
        $startTime = Carbon::parse($start);
        $endTime = Carbon::parse($end);

        return max(1, $startTime->diffInHours($endTime));
    }
}
