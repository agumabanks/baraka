<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class Ics2Controller extends Controller
{
    public function index()
    {
        return view('backend.admin.placeholder', ['title' => 'ICS2 (ENS) Readiness']);
    }
}

