<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EdiProvider;
use Illuminate\Http\Request;

class EdiController extends Controller
{
    public function index()
    {
        $providers = EdiProvider::all();

        return view('backend.admin.edi.index', compact('providers'));
    }

    public function create()
    {
        return view('backend.admin.edi.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|in:airline,broker,mock',
            'config' => 'nullable|array',
        ]);
        EdiProvider::create($data);

        return redirect()->route('admin.edi.index')->with('status', 'Provider created');
    }
}
