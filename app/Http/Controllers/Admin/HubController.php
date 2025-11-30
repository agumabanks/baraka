<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\Hub;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HubController extends Controller
{
    public function index(): View
    {
        $hubs = Hub::with('parent')->latest()->paginate(20);
        
        $stats = [
            'total' => Hub::count(),
            'active' => Hub::where('status', 1)->count(),
        ];

        return view('admin.hubs.index', compact('hubs', 'stats'));
    }

    public function show(Hub $hub): View
    {
        // Only load relationships that exist
        $hub->load(['parent', 'children']);
        
        return view('admin.hubs.show', compact('hub'));
    }
}
