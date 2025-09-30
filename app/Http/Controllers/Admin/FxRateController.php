<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FxRate;

class FxRateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', FxRate::class);
        $items = FxRate::query()->orderByDesc('effective_at')->paginate(20);

        return view('backend.admin.fx_rates.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('create', FxRate::class);

        return view('backend.admin.fx_rates.create');
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $this->authorize('create', FxRate::class);
        $data = $request->validate([
            'base' => 'required|string|size:3',
            'counter' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0.000001',
            'provider' => 'required|string',
            'effective_at' => 'required|date',
        ]);
        FxRate::create($data);

        return redirect()->route('admin.fx.index')->with('status', 'FX rate added');
    }

    public function edit(FxRate $fx)
    {
        $this->authorize('update', $fx);

        return view('backend.admin.fx_rates.edit', ['fx' => $fx]);
    }

    public function update(\Illuminate\Http\Request $request, FxRate $fx)
    {
        $this->authorize('update', $fx);
        $data = $request->validate([
            'rate' => 'required|numeric|min:0.000001',
            'effective_at' => 'required|date',
        ]);
        $fx->update($data);

        return redirect()->route('admin.fx.index')->with('status', 'FX rate updated');
    }

    public function destroy(FxRate $fx)
    {
        $this->authorize('delete', $fx);
        $fx->delete();

        return back()->with('status', 'FX rate deleted');
    }
}
