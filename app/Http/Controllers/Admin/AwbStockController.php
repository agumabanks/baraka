<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwbStock;
use Illuminate\Http\Request;

class AwbStockController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AwbStock::class);
        $query = AwbStock::query()->latest('id');
        $user = $request->user();
        if (! $user->hasRole(['hq_admin', 'admin', 'super-admin']) && $user->hub_id) {
            $query->where('hub_id', $user->hub_id);
        }
        $items = $query->paginate(15);

        return view('backend.admin.awb_stock.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('create', AwbStock::class);

        return view('backend.admin.awb_stock.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', AwbStock::class);
        $data = $request->validate([
            'carrier_code' => 'required|string|size:2',
            'iata_prefix' => 'required|string|size:3',
            'range_start' => 'required|integer|min:1',
            'range_end' => 'required|integer|gte:range_start',
        ]);
        $data['hub_id'] = $request->user()->hub_id;
        $data['status'] = 'active';
        AwbStock::create($data);

        return redirect()->route('admin.awb-stock.index')->with('status', 'AWB stock created');
    }

    public function edit(AwbStock $awb_stock)
    {
        $this->authorize('update', $awb_stock);

        return view('backend.admin.awb_stock.edit', ['stock' => $awb_stock]);
    }

    public function update(Request $request, AwbStock $awb_stock)
    {
        $this->authorize('update', $awb_stock);
        $data = $request->validate([
            'used_count' => 'nullable|integer|min:0',
            'voided_count' => 'nullable|integer|min:0',
            'status' => 'required|in:active,exhausted,voided',
        ]);
        $awb_stock->update($data);

        return redirect()->route('admin.awb-stock.index')->with('status', 'AWB stock updated');
    }

    public function destroy(AwbStock $awb_stock)
    {
        $this->authorize('delete', $awb_stock);
        $awb_stock->delete();

        return back()->with('status', 'AWB stock deleted');
    }
}
