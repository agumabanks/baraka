<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScanEvent;
use Illuminate\Http\Request;

class ScanEventController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ScanEvent::class);
        $events = ScanEvent::latest()->paginate(15);
        return view('backend.admin.placeholder', ['title' => 'Scan Events', 'items' => $events]);
    }

    public function show(ScanEvent $scan)
    {
        $this->authorize('view', $scan);
        return view('backend.admin.placeholder', ['title' => 'Scan #'.$scan->id, 'record' => $scan]);
    }

    public function create()
    {
        $this->authorize('create', ScanEvent::class);
        return view('backend.admin.placeholder', ['title' => 'Create Scan Event']);
    }

    public function store(Request $request)
    {
        $this->authorize('create', ScanEvent::class);
        return back()->with('status','Scan creation not yet implemented');
    }
}

