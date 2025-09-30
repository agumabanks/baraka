<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierService;
use Illuminate\Http\Request;

class CarrierServiceController extends Controller
{
    public function index()
    {
        $services = CarrierService::with('carrier')->paginate(20);

        return view('backend.admin.carrier_services.index', compact('services'));
    }

    public function create()
    {
        $carriers = Carrier::orderBy('name')->get();

        return view('backend.admin.carrier_services.create', compact('carriers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:100',
            'requires_eawb' => 'nullable|boolean',
        ]);
        $data['requires_eawb'] = (bool) ($data['requires_eawb'] ?? false);
        CarrierService::create($data);

        return redirect()->route('admin.carrier-services.index')->with('status', 'Service created');
    }

    public function edit(CarrierService $carrier_service)
    {
        $carriers = Carrier::orderBy('name')->get();

        return view('backend.admin.carrier_services.edit', ['service' => $carrier_service, 'carriers' => $carriers]);
    }

    public function update(Request $request, CarrierService $carrier_service)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'requires_eawb' => 'nullable|boolean',
        ]);
        $data['requires_eawb'] = (bool) ($data['requires_eawb'] ?? false);
        $carrier_service->update($data);

        return redirect()->route('admin.carrier-services.index')->with('status', 'Service updated');
    }
}
