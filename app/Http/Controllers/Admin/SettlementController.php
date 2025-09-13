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
        return view('backend.admin.placeholder', ['title' => 'Settlements', 'items' => $items]);
    }

    public function show(Settlement $settlement)
    {
        $this->authorize('view', $settlement);
        return view('backend.admin.placeholder', ['title' => 'Settlement #'.$settlement->id, 'record' => $settlement]);
    }
}

