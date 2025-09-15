<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;

class SettlementController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Settlement::class);
        $items = Settlement::latest()->paginate(15);
        return view('backend.admin.settlements.index', compact('items'));
    }

    public function show(Settlement $settlement)
    {
        $this->authorize('view', $settlement);
        return view('backend.admin.settlements.show', ['settlement' => $settlement]);
    }
}
