<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accident\StoreRequest;
use App\Models\Backend\Asset;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Vehicle;
use App\Repositories\Accident\AccidentInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class AccidentController extends Controller
{
    protected $repo;
    public function __construct(AccidentInterface $repo)
    {
        $this->repo       = $repo;
    }
    public function index()
    {
        $accidents = $this->repo->all();
        return view('backend.accident.index',compact('accidents'));
    }
    public function create()
    {
        $assets = Asset::orderBy('id','desc')->get();
        $deliverymans = DeliveryMan::where('status',Status::ACTIVE)->orderBy('id','desc')->get();
        return view('backend.accident.create',compact('assets','deliverymans'));
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('Accident successfully added.',__('message.success'));
            return redirect()->route('accident.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $assets = Asset::orderBy('id','desc')->get();
        $accident        =   $this->repo->get($id);
        $deliverymans = DeliveryMan::where('status',Status::ACTIVE)->orderBy('id','desc')->get();
        return view('backend.accident.edit',compact('assets','accident','deliverymans'));
    }

    public function update(StoreRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success('Accident successfully updated.',__('message.success'));
            return redirect()->route('accident.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function delete($id)
    {
        $this->repo->delete($id);
        Toastr::success('Accident successfully deleted.',__('message.success'));
        return back();
    }

}
