<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lane;
use App\Models\Zone;
use Illuminate\Http\Request;

class LaneController extends Controller
{
    public function index()
    {
        $lanes = Lane::with(['origin','destination'])->paginate(20);
        return view('backend.admin.lanes.index', compact('lanes'));
    }

    public function create()
    {
        $zones = Zone::orderBy('name')->get();
        return view('backend.admin.lanes.create', compact('zones'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'origin_zone_id' => 'required|exists:zones,id',
            'dest_zone_id' => 'required|exists:zones,id',
            'mode' => 'required|in:air,road',
            'std_transit_days' => 'required|integer|min:0',
            'dim_divisor' => 'required|integer|in:5000,6000',
            'eawb_required' => 'nullable|boolean',
        ]);
        $data['eawb_required'] = (bool)($data['eawb_required'] ?? false);
        Lane::create($data);
        return redirect()->route('admin.lanes.index')->with('status', 'Lane created');
    }

    public function edit(Lane $lane)
    {
        $zones = Zone::orderBy('name')->get();
        return view('backend.admin.lanes.edit', compact('lane','zones'));
    }

    public function update(Request $request, Lane $lane)
    {
        $data = $request->validate([
            'std_transit_days' => 'required|integer|min:0',
            'dim_divisor' => 'required|integer|in:5000,6000',
            'eawb_required' => 'nullable|boolean',
        ]);
        $data['eawb_required'] = (bool)($data['eawb_required'] ?? false);
        $lane->update($data);
        return redirect()->route('admin.lanes.index')->with('status', 'Lane updated');
    }
}

