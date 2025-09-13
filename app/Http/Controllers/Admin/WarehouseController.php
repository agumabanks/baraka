<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhLocation;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', WhLocation::class);
        $query = WhLocation::query();
        if ($request->user()->hub_id) { $query->where('branch_id', $request->user()->hub_id); }
        $items = $query->orderBy('code')->paginate(30);
        return view('backend.admin.placeholder', ['title'=>'Warehouse Locations','items'=>$items]);
    }
}

