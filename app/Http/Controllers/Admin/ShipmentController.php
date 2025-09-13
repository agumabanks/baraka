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

        $query = Shipment::query()->latest();

        $user = $request->user();
        if (!$user->hasRole('hq_admin') && !is_null($user->hub_id)) {
            $query->where(function ($q) use ($user) {
                $q->where('origin_branch_id', $user->hub_id)
                  ->orWhere('dest_branch_id', $user->hub_id);
            });
        }

        $shipments = $query->select(['id','tracking','current_status','origin_branch_id','dest_branch_id'])->paginate(15);
        return view('backend.admin.placeholder', [
            'title' => 'Shipments',
            'items' => $shipments,
        ]);
    }

    public function show(Shipment $shipment)
    {
        $this->authorize('view', $shipment);
        return view('backend.admin.placeholder', [
            'title' => 'Shipment #'.$shipment->id,
            'record' => $shipment,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Shipment::class);
        return view('backend.admin.placeholder', ['title' => 'Create Shipment']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Shipment::class);
        return back()->with('status','Shipment creation form not yet implemented');
    }

    public function edit(Shipment $shipment)
    {
        $this->authorize('update', $shipment);
        return view('backend.admin.placeholder', ['title' => 'Edit Shipment #'.$shipment->id, 'record' => $shipment]);
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

