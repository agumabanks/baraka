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

        return view('backend.admin.scans.index', compact('events'));
    }

    public function show(ScanEvent $scan)
    {
        $this->authorize('view', $scan);

        return view('backend.admin.scans.show', ['scan' => $scan]);
    }

    public function create()
    {
        $this->authorize('create', ScanEvent::class);

        return view('backend.admin.scans.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', ScanEvent::class);
        $payload = $request->validate([
            'sscc' => 'required|string',
            'type' => 'required|string',
            'branch_id' => 'nullable|exists:branches,id',
            'leg_id' => 'nullable|exists:transport_legs,id',
            'occurred_at' => 'nullable|date',
            'note' => 'nullable|string',
        ]);
        $payload['user_id'] = $request->user()->id;
        $payload['occurred_at'] = $payload['occurred_at'] ?? now();
        ScanEvent::create($payload);

        return redirect()->route('admin.scans.index')->with('status', 'Scan created');
    }
}
