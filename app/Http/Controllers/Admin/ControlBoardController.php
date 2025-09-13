<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ControlBoardController extends Controller
{
    public function index()
    {
        // Gate via permissions if desired; default allow authenticated users with dashboard access
        return view('backend.admin.placeholder', ['title' => 'Control Board']);
    }
}

