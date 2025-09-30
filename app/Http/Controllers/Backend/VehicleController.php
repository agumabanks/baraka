<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\StoreRequest;
use App\Models\Backend\Accident;
use App\Models\Backend\Asset;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Fuel;
use App\Models\Backend\Maintenance;
use App\Repositories\Vehicles\VehiclesInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    protected $repo;

    public function __construct(VehiclesInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $vehicles = $this->repo->all();
        $deliverymans = DeliveryMan::where('status', Status::ACTIVE)->get();

        return view('backend.vehicles.index', compact('vehicles', 'request', 'deliverymans'));
    }

    public function view($id)
    {
        $vehicle = $this->repo->get($id);
        $fuels = Fuel::where('vehicle_id', $id)->orderBy('id', 'desc')->paginate(10, '*', 'fuels');
        $assets = Asset::where('vehicle_id', $id)->orderBy('id', 'desc')->paginate(10, '*', 'assets');
        $maintenances = Maintenance::where('vehicle_id', $id)->orderBy('id', 'desc')->paginate(10, '*', 'maintenances');
        $accidents = Accident::where('vehicle_id', $id)->orderBy('id', 'desc')->paginate(10, '*', 'accidents');

        return view('backend.vehicles.view', compact('vehicle', 'fuels', 'assets', 'maintenances', 'accidents'));
    }

    public function create()
    {
        return view('backend.designation.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Designation successfully added.', __('message.success'));

            return redirect()->route('designations.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $designation = $this->repo->get($id);

        return view('backend.designation.edit', compact('designation'));
    }

    public function update(StoreRequest $request)
    {
        if ($this->repo->update($request->id, $request)) {
            Toastr::success('Designation successfully updated.', __('message.success'));

            return redirect()->route('designations.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Designation successfully deleted.',__('message.success'));

        return back();
    }
}
