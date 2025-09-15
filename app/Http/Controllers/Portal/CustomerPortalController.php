<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;

class CustomerPortalController extends Controller
{
    public function index()
    {
        return view('frontend.portal.index');
    }
}

