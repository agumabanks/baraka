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
        if (!$user->hasRole(['hq_admin','admin','super-admin']) && $user->hub_id) {
            $query->where('hub_id', $user->hub_id);
        }
        $items = $query->paginate(15);
        return view('backend.admin.placeholder', ['title'=>'AWB Stock','items'=>$items]);
    }

    public function create()
    {
        $this->authorize('create', AwbStock::class);
        return view('backend.admin.placeholder', ['title'=>'Add AWB Stock']);
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
        return redirect()->route('admin.awb-stock.index')->with('status','AWB stock created');
    }
}

