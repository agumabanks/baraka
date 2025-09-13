<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportLeg;
use Illuminate\Http\Request;

class TransportLegController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', TransportLeg::class);
        $legs = TransportLeg::latest()->paginate(15);
        return view('backend.admin.placeholder', ['title' => 'Linehaul Legs', 'items' => $legs]);
    }

    public function show(TransportLeg $linehaul_leg)
    {
        // Route model binding will name parameter by resource 'linehaul-legs' => variable $linehaul_leg
        $this->authorize('view', $linehaul_leg);
        return view('backend.admin.placeholder', ['title' => 'Leg #'.$linehaul_leg->id, 'record' => $linehaul_leg]);
    }

    public function create()
    {
        $this->authorize('create', TransportLeg::class);
        return view('backend.admin.placeholder', ['title' => 'Create Linehaul Leg']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', TransportLeg::class);
        return back()->with('status','Leg creation not yet implemented');
    }

    public function update(Request $request, TransportLeg $linehaul_leg)
    {
        $this->authorize('update', $linehaul_leg);
        return back()->with('status','Leg update not yet implemented');
    }
}

