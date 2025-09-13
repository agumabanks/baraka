<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index()
    {
        return view('backend.admin.placeholder', ['title' => 'API Keys']);
    }

    public function store(Request $request)
    {
        return back()->with('status','API key generation not implemented');
    }

    public function destroy($id)
    {
        return back()->with('status','API key delete not implemented');
    }
}

