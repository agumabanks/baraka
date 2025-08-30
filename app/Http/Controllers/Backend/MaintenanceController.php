<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreRequest;
use App\Models\Backend\Asset;
use App\Models\Backend\Maintenance;
use App\Models\Backend\Vehicle;
use App\Repositories\Maintenance\MaintenanceInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{

    protected $repo;
    public function __construct(MaintenanceInterface $repo)
    {
        $this->repo       = $repo;
    }
    public function index()
    {
        if(request()->id):
            $maintenances = Maintenance::where('id',request()->id)->paginate(10);
        else:
            $maintenances = $this->repo->all();
        endif;
        return view('backend.maintenance.index',compact('maintenances'));
    }
    public function create()
    {
        $assets = Asset::orderBy('id','desc')->get();
        return view('backend.maintenance.create',compact('assets'));
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('Maintenance successfully added.',__('message.success'));
            return redirect()->route('maintenance.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $assets = Asset::orderBy('id','desc')->get();
        $maintenance     =   $this->repo->get($id);
        return view('backend.maintenance.edit',compact('assets','maintenance'));
    }

    public function update(StoreRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success('Maintenance successfully updated.',__('message.success'));
            return redirect()->route('maintenance.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function delete($id)
    {
        $this->repo->delete($id);
        Toastr::success('Maintenance successfully deleted.',__('message.success'));
        return back();
    }

}
