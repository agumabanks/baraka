<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Shipment::class);

        $query = Shipment::query();

        // Filters: q (tracking/id), status, branch, date_from, date_to
        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($sq) use ($q) {
                $sq->where('id', $q)
                   ->orWhere('tracking', 'like', "%$q%");
            });
        }
        if ($status = $request->input('status')) {
            $query->where('current_status', $status);
        }
        if ($branchId = $request->input('branch')) {
            $query->where(function ($sq) use ($branchId) {
                $sq->where('origin_branch_id', $branchId)
                   ->orWhere('dest_branch_id', $branchId);
            });
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $user = $request->user();
        if (!$user->hasRole('hq_admin') && !is_null($user->hub_id)) {
            $query->where(function ($q) use ($user) {
                $q->where('origin_branch_id', $user->hub_id)
                  ->orWhere('dest_branch_id', $user->hub_id);
            });
        }

        $shipments = $query->latest()->select(['id','current_status','origin_branch_id','dest_branch_id','created_at'])->paginate(15)->withQueryString();
        return view('backend.admin.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment)
    {
        $this->authorize('view', $shipment);
        return view('backend.admin.shipments.show', compact('shipment'));
    }

    public function create()
    {
        $this->authorize('create', Shipment::class);
        return view('backend.admin.shipments.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Shipment::class);
        return back()->with('status','Shipment creation form not yet implemented');
    }

    public function edit(Shipment $shipment)
    {
        $this->authorize('update', $shipment);
        return view('backend.admin.shipments.edit', compact('shipment'));
    }

    public function update(Request $request, Shipment $shipment)
    {
        $this->authorize('update', $shipment);
        return back()->with('status','Shipment update not yet implemented');
    }

    public function labels(Shipment $shipment)
    {
        $this->authorize('view', $shipment);
        return response('Label generation not implemented yet', 501);
    }
}
