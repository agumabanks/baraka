<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeniedPartyController extends Controller
{
    public function index()
    {
        return view('backend.admin.dps.index');
    }

    public function run(Request $request)
    {
        // Placeholder for CSL API call; log input and return
        return back()->with('status', 'DPS run queued (placeholder)');
    }
}
