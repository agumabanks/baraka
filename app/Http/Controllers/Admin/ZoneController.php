<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::query()->paginate(20);
        return view('backend.admin.zones.index', compact('zones'));
    }

    public function create()
    {
        return view('backend.admin.zones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:zones,code',
            'name' => 'required|string|max:100',
            'countries' => 'nullable|array',
        ]);
        Zone::create($data);
        return redirect()->route('admin.zones.index')->with('status', 'Zone created');
    }

    public function edit(Zone $zone)
    {
        return view('backend.admin.zones.edit', compact('zone'));
    }

    public function update(Request $request, Zone $zone)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'countries' => 'nullable|array',
        ]);
        $zone->update($data);
        return redirect()->route('admin.zones.index')->with('status', 'Zone updated');
    }
}

