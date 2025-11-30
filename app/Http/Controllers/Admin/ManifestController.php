<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manifest;

class ManifestController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Manifest::class);
        $items = Manifest::query()->latest('id')->paginate(15);

        return view('backend.admin.manifests.index', compact('items'));
    }

    public function show(Manifest $manifest)
    {
        $this->authorize('view', $manifest);

        return view('backend.admin.manifests.show', compact('manifest'));
    }

    public function create()
    {
        $this->authorize('create', Manifest::class);

        return view('backend.admin.manifests.create');
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $this->authorize('create', Manifest::class);
        $data = $request->validate([
            'number' => 'required|string',
            'mode' => 'required|in:air,road',
            'departure_at' => 'required|date',
            'arrival_at' => 'nullable|date|after_or_equal:departure_at',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:open,closed,departed,arrived',
        ]);
        $m = Manifest::create($data);

        return redirect()->route('admin.manifests.show', $m)->with('status', 'Manifest created');
    }

    public function edit(Manifest $manifest)
    {
        $this->authorize('update', $manifest);

        return view('backend.admin.manifests.edit', compact('manifest'));
    }

    public function update(\Illuminate\Http\Request $request, Manifest $manifest)
    {
        $this->authorize('update', $manifest);
        $data = $request->validate([
            'status' => 'required|in:open,closed,departed,arrived',
            'arrival_at' => 'nullable|date',
        ]);
        $manifest->update($data);

        return redirect()->route('admin.manifests.show', $manifest)->with('status', 'Manifest updated');
    }

    public function destroy(Manifest $manifest)
    {
        $this->authorize('delete', $manifest);
        $manifest->delete();

        return redirect()->route('admin.manifests.index')->with('status', 'Manifest deleted');
    }
}
