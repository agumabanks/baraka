<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScanEvent;
use Carbon\Carbon;

class ExceptionTowerController extends Controller
{
    public function index()
    {
        $staleThreshold = Carbon::now()->subHours(12);
        // Count scans older than threshold (using occurred_at if available)
        $staleCount = ScanEvent::query()
            ->where('occurred_at', '<', $staleThreshold)
            ->count();
        return view('backend.admin.exception_tower.index', compact('staleCount', 'staleThreshold'));
    }
}
