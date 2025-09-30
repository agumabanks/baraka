<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\Hub;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function index(Request $request)
    {
        $hubs = Hub::orderBy('id', 'desc')->paginate(15);

        return view('backend.hub.index', compact('hubs', 'request'));
    }

    public function filter(Request $request)
    {
        $query = Hub::query();

        if ($request->has('name') && ! empty($request->name)) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->has('phone') && ! empty($request->phone)) {
            $query->where('phone', 'like', '%'.$request->phone.'%');
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $hubs = $query->orderBy('id', 'desc')->paginate(15);

        return view('backend.hub.index', compact('hubs', 'request'));
    }

    public function create()
    {
        return view('backend.hub.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        try {
            Hub::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => $request->status ?? 1,
            ]);

            Toastr::success('Hub successfully created.', __('message.success'));

            return redirect()->route('hubs.index');
        } catch (\Exception $e) {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $hub = Hub::findOrFail($id);

        return view('backend.hub.edit', compact('hub'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        try {
            $hub = Hub::findOrFail($request->id);
            $hub->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => $request->status ?? 1,
            ]);

            Toastr::success('Hub successfully updated.', __('message.success'));

            return redirect()->route('hubs.index');
        } catch (\Exception $e) {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        try {
            $hub = Hub::findOrFail($id);

            // Check if hub has related records
            if ($hub->parcels()->count() > 0) {
                Toastr::error('Cannot delete hub with existing parcels.', __('message.error'));

                return back();
            }

            $hub->delete();
            Toastr::success('Hub successfully deleted.', __('message.success'));

            return back();
        } catch (\Exception $e) {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }

    public function view($id)
    {
        $hub = Hub::with(['parcels', 'children', 'parent'])->findOrFail($id);

        return view('backend.hub.view', compact('hub'));
    }

    public function parcelHubs()
    {
        $hubs = Hub::active()->orderBy('name')->get();

        return response()->json($hubs);
    }
}
