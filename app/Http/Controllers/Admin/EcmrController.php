<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ecmr;

class EcmrController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Ecmr::class);
        $items = Ecmr::query()->latest('id')->paginate(15);

        return view('backend.admin.ecmr.index', compact('items'));
    }

    public function show(Ecmr $ecmr)
    {
        $this->authorize('view', $ecmr);

        return view('backend.admin.ecmr.show', compact('ecmr'));
    }

    public function create()
    {
        $this->authorize('create', Ecmr::class);

        return view('backend.admin.ecmr.create');
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $this->authorize('create', Ecmr::class);
        $data = $request->validate([
            'cmr_number' => 'required|string',
            'road_carrier' => 'required|string',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'status' => 'required|in:draft,issued,delivered',
        ]);
        $ec = Ecmr::create($data);

        return redirect()->route('admin.ecmr.show', $ec)->with('status', 'e-CMR created');
    }

    public function edit(Ecmr $ecmr)
    {
        $this->authorize('update', $ecmr);

        return view('backend.admin.ecmr.edit', compact('ecmr'));
    }

    public function update(\Illuminate\Http\Request $request, Ecmr $ecmr)
    {
        $this->authorize('update', $ecmr);
        $data = $request->validate([
            'status' => 'required|in:draft,issued,delivered',
        ]);
        $ecmr->update($data);

        return redirect()->route('admin.ecmr.show', $ecmr)->with('status', 'e-CMR updated');
    }
}
