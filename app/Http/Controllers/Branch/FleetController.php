<?php

namespace App\Http\Controllers\Branch;

use App\Enums\RosterStatus;
use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\BranchAlert;
use App\Models\Driver;
use App\Models\DriverRoster;
use App\Models\Backend\Vehicle;
use App\Models\VehicleTrip;
use App\Models\VehicleMaintenance;
use App\Support\BranchCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FleetController extends Controller
{
    use ResolvesBranch;

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $vehicles = BranchCache::rememberBoard($branch->id, 'fleet', function () use ($branch) {
            return Vehicle::query()
                ->where('branch_id', $branch->id)
                ->orderByDesc('id')
                ->get();
        });

        $rosters = DriverRoster::query()
            ->with(['driver.user:id,name'])
            ->where('branch_id', $branch->id)
            ->latest()
            ->limit(8)
            ->get();

        $downtimeAlerts = BranchAlert::query()
            ->where('branch_id', $branch->id)
            ->where('alert_type', 'FLEET')
            ->latest()
            ->limit(5)
            ->get();

        $driverPool = Driver::query()->with('user:id,name')->where('branch_id', $branch->id)->get();

        return view('branch.fleet', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'vehicles' => $vehicles,
            'rosters' => $rosters,
            'downtimeAlerts' => $downtimeAlerts,
            'driverPool' => $driverPool,
        ]);
    }

    public function updateVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($vehicle->branch_id === $branch->id, 403);

        $data = $request->validate([
            'status' => 'required|string|max:30',
        ]);

        $vehicle->status = $data['status'];
        $vehicle->save();

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Vehicle status updated.');
    }

    public function storeRoster(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'driver_id' => 'required|integer|exists:drivers,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'shift_type' => 'nullable|string|max:40',
        ]);

        DriverRoster::create([
            'driver_id' => $data['driver_id'],
            'branch_id' => $branch->id,
            'shift_type' => $data['shift_type'] ?? 'STANDARD',
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => RosterStatus::SCHEDULED,
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Driver roster scheduled.');
    }

    public function trips(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $status = $request->get('status');

        $trips = VehicleTrip::query()
            ->forBranch($branch->id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['vehicle:id,plate_no,model', 'driver.user:id,name', 'stops'])
            ->latest('planned_start_at')
            ->paginate(15);

        $activeTrips = VehicleTrip::forBranch($branch->id)->active()->count();
        $completedToday = VehicleTrip::forBranch($branch->id)
            ->completed()
            ->whereDate('actual_end_at', today())
            ->count();

        $vehicles = Vehicle::where('branch_id', $branch->id)
            ->where('status', 'active')
            ->get();

        $drivers = Driver::where('branch_id', $branch->id)
            ->where('status', 1)
            ->with('user:id,name')
            ->get();

        return view('branch.fleet_trips', compact(
            'branch',
            'trips',
            'activeTrips',
            'completedToday',
            'vehicles',
            'drivers',
            'status'
        ));
    }

    public function storeTrip(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:drivers,id',
            'trip_type' => 'required|string|in:delivery,pickup,transfer,route',
            'route_name' => 'nullable|string|max:100',
            'destination_branch_id' => 'nullable|exists:branchs,id',
            'planned_start_at' => 'required|date',
            'planned_end_at' => 'nullable|date|after:planned_start_at',
            'planned_distance_km' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        VehicleTrip::create([
            'branch_id' => $branch->id,
            'origin_branch_id' => $branch->id,
            'vehicle_id' => $data['vehicle_id'],
            'driver_id' => $data['driver_id'],
            'trip_type' => $data['trip_type'],
            'route_name' => $data['route_name'] ?? null,
            'destination_branch_id' => $data['destination_branch_id'] ?? null,
            'planned_start_at' => $data['planned_start_at'],
            'planned_end_at' => $data['planned_end_at'] ?? null,
            'planned_distance_km' => $data['planned_distance_km'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'planned',
        ]);

        return back()->with('success', 'Trip created successfully.');
    }

    public function startTrip(Request $request, VehicleTrip $trip): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($trip->branch_id === $branch->id, 403);

        $trip->start();

        return back()->with('success', 'Trip started.');
    }

    public function completeTrip(Request $request, VehicleTrip $trip): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($trip->branch_id === $branch->id, 403);

        $data = $request->validate([
            'actual_distance_km' => 'nullable|numeric|min:0',
            'fuel_consumption_liters' => 'nullable|numeric|min:0',
        ]);

        $trip->update([
            'actual_distance_km' => $data['actual_distance_km'] ?? null,
            'fuel_consumption_liters' => $data['fuel_consumption_liters'] ?? null,
        ]);

        $trip->complete();

        return back()->with('success', 'Trip completed.');
    }

    public function maintenance(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $status = $request->get('status');

        $maintenanceRecords = VehicleMaintenance::query()
            ->forBranch($branch->id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['vehicle:id,plate_no,model', 'reportedBy:id,name'])
            ->latest('scheduled_at')
            ->paginate(15);

        $pendingCount = VehicleMaintenance::forBranch($branch->id)->pending()->count();
        $overdueCount = VehicleMaintenance::forBranch($branch->id)->overdue()->count();

        $vehicles = Vehicle::where('branch_id', $branch->id)->get();

        return view('branch.fleet_maintenance', compact(
            'branch',
            'maintenanceRecords',
            'pendingCount',
            'overdueCount',
            'vehicles',
            'status'
        ));
    }

    public function storeMaintenance(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'maintenance_type' => 'required|string|in:routine,repair,inspection,emergency',
            'category' => 'nullable|string|max:50',
            'description' => 'required|string',
            'scheduled_at' => 'required|date',
            'priority' => 'nullable|string|in:low,normal,high,critical',
        ]);

        VehicleMaintenance::create([
            'branch_id' => $branch->id,
            'vehicle_id' => $data['vehicle_id'],
            'reported_by_user_id' => $user->id,
            'maintenance_type' => $data['maintenance_type'],
            'category' => $data['category'] ?? null,
            'description' => $data['description'],
            'scheduled_at' => $data['scheduled_at'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'scheduled',
        ]);

        return back()->with('success', 'Maintenance scheduled.');
    }

    public function completeMaintenance(Request $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($maintenance->branch_id === $branch->id, 403);

        $data = $request->validate([
            'work_performed' => 'required|string',
            'parts_cost' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'odometer_reading' => 'nullable|integer|min:0',
        ]);

        $maintenance->update(['performed_by_user_id' => $user->id]);
        $maintenance->complete($data);

        return back()->with('success', 'Maintenance completed.');
    }
}
