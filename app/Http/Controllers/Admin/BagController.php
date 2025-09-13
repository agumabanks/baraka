<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bag;
use Illuminate\Http\Request;

class BagController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Bag::class);
        $bags = Bag::latest()->paginate(15);
        return view('backend.admin.placeholder', ['title' => 'Bags', 'items' => $bags]);
    }

    public function show(Bag $bag)
    {
        $this->authorize('view', $bag);
        return view('backend.admin.placeholder', ['title' => 'Bag #'.$bag->id, 'record' => $bag]);
    }

    public function create()
    {
        $this->authorize('create', Bag::class);
        return view('backend.admin.placeholder', ['title' => 'Create Bag']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Bag::class);
        return back()->with('status','Bag creation not yet implemented');
    }

    public function update(Request $request, Bag $bag)
    {
        $this->authorize('update', $bag);
        return back()->with('status','Bag update not yet implemented');
    }
}

