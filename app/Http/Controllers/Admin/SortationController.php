<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SortationBin;
use Illuminate\Http\Request;

class SortationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SortationBin::class);
        $query = SortationBin::query();
        $user = $request->user();
        if ($user->hub_id) { $query->where('branch_id', $user->hub_id); }
        $items = $query->orderBy('code')->paginate(30);
        return view('backend.admin.placeholder', ['title'=>'Sortation','items'=>$items]);
    }

    public function update(Request $request, SortationBin $sortation)
    {
        $this->authorize('update', $sortation);
        $sortation->update(['status' => $request->get('status','active')]);
        return back()->with('status','Sortation bin updated');
    }
}

