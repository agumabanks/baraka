<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\Backend\Vehicle;
use App\Models\Driver;
use App\Models\Manifest;
use App\Services\FleetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManifestController extends Controller
{
    use ResolvesBranch;

    protected FleetService $fleetService;

    public function __construct(FleetService $fleetService)
    {
        $this->fleetService = $fleetService;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $manifests = Manifest::query()
            ->where('origin_branch_id', $branch->id)
            ->orWhere('destination_branch_id', $branch->id)
            ->with(['driver', 'vehicle', 'originBranch', 'destinationBranch'])
            ->latest()
            ->paginate(15);

        return view('branch.manifests.index', compact('branch', 'manifests'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $drivers = Driver::where('branch_id', $branch->id)->where('status', 'ACTIVE')->get();
        $vehicles = Vehicle::where('branch_id', $branch->id)->where('status', 'ACTIVE')->get();
        $branches = \App\Models\Backend\Branch::where('id', '!=', $branch->id)->get();

        return view('branch.manifests.create', compact('branch', 'drivers', 'vehicles', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        
        $data = $request->validate([
            'mode' => 'required|in:road,air',
            'type' => 'required|in:INTERNAL,3PL',
            'destination_branch_id' => 'required|exists:branches,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'departure_at' => 'nullable|date',
        ]);

        try {
            $manifest = $this->fleetService->createManifest($data, $user);
            return redirect()->route('branch.manifests.show', $manifest)->with('success', 'Manifest created.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, Manifest $manifest): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($manifest->origin_branch_id === $branch->id || $manifest->destination_branch_id === $branch->id, 403);

        $manifest->load(['items.manifestable', 'driver', 'vehicle']);

        return view('branch.manifests.show', compact('branch', 'manifest'));
    }

    public function dispatchManifest(Request $request, Manifest $manifest): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        
        try {
            $this->fleetService->dispatchManifest($manifest);
            return back()->with('success', 'Manifest dispatched.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function arriveManifest(Request $request, Manifest $manifest): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        
        try {
            $this->fleetService->arriveManifest($manifest, $user);
            return back()->with('success', 'Manifest arrived.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
