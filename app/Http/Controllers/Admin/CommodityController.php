<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use Illuminate\Http\Request;

class CommodityController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Commodity::class);
        $items = Commodity::latest()->paginate(15);
        return view('backend.admin.commodities.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('create', Commodity::class);
        return view('backend.admin.commodities.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Commodity::class);
        return back()->with('status','Commodity creation not yet implemented');
    }

    public function update(Request $request, Commodity $commodity)
    {
        $this->authorize('update', $commodity);
        return back()->with('status','Commodity update not yet implemented');
    }
}
