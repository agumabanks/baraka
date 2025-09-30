<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Http\Request;

class CarrierController extends Controller
{
    public function index()
    {
        $carriers = Carrier::paginate(20);

        return view('backend.admin.carriers.index', compact('carriers'));
    }

    public function create()
    {
        return view('backend.admin.carriers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:5|unique:carriers,code',
            'mode' => 'required|in:air,road',
        ]);
        Carrier::create($data);

        return redirect()->route('admin.carriers.index')->with('status', 'Carrier created');
    }

    public function edit(Carrier $carrier)
    {
        return view('backend.admin.carriers.edit', compact('carrier'));
    }

    public function update(Request $request, Carrier $carrier)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'mode' => 'required|in:air,road',
        ]);
        $carrier->update($data);

        return redirect()->route('admin.carriers.index')->with('status', 'Carrier updated');
    }
}
