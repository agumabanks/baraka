<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ObservabilityController extends Controller
{
    public function index()
    {
        $failedJobs = DB::table('failed_jobs')->count();
        return view('backend.admin.observability.index', compact('failedJobs'));
    }
}

