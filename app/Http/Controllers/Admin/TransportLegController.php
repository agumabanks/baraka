<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportLeg;
use Illuminate\Http\Request;

class TransportLegController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', TransportLeg::class);
        $legs = TransportLeg::latest()->paginate(15);
        return view('backend.admin.linehaul_legs.index', compact('legs'));
    }

    public function show(TransportLeg $linehaul_leg)
    {
        // Route model binding will name parameter by resource 'linehaul-legs' => variable $linehaul_leg
        $this->authorize('view', $linehaul_leg);
        return view('backend.admin.linehaul_legs.show', ['leg' => $linehaul_leg]);
    }

    public function create()
    {
        $this->authorize('create', TransportLeg::class);
        return view('backend.admin.linehaul_legs.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', TransportLeg::class);
        $data = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'mode' => 'required|in:AIR,ROAD',
            'carrier' => 'nullable|string|max:100',
            'flight_number' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'awb' => 'nullable|string|max:50',
            'cmr' => 'nullable|string|max:50',
            'depart_at' => 'nullable|date',
            'arrive_at' => 'nullable|date|after_or_equal:depart_at',
            'status' => 'required|string',
        ]);
        $leg = TransportLeg::create($data);
        return redirect()->route('admin.linehaul-legs.index')->with('status','Leg #'.$leg->id.' created');
    }

    public function update(Request $request, TransportLeg $linehaul_leg)
    {
        $this->authorize('update', $linehaul_leg);
        $data = $request->validate([
            'carrier' => 'nullable|string|max:100',
            'flight_number' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'awb' => 'nullable|string|max:50',
            'cmr' => 'nullable|string|max:50',
            'depart_at' => 'nullable|date',
            'arrive_at' => 'nullable|date|after_or_equal:depart_at',
            'status' => 'required|string',
        ]);
        $linehaul_leg->update($data);
        return redirect()->route('admin.linehaul-legs.index')->with('status','Leg updated');
    }
}
