<?php
namespace App\Repositories\Vehicles;
use App\Models\Backend\Vehicle;
use Carbon\Carbon;

class VehiclesRepository implements VehiclesInterface{
    public function all(){
        return Vehicle::where(function($query){
            if(request()->name):
                $query->where('name','like','%'.request()->name.'%');
            endif;
            if(request()->status || request()->status == '0'):
                $query->where('status',request()->status);
            endif;
            if(request()->driver_id):
                $query->where('driver_id',request()->driver_id);
            endif;
            if(request()->date):

                $date = explode('To', request()->date);
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();

                $query->whereBetween('created_at',[$from,$to]);
            endif;
        })->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return Vehicle::find($id);
    }

    public function store($request){
        try {
            $vehicle                 = new Vehicle();
            $vehicle->plate_no       = $request->plate_no;
            $vehicle->chasis_number  = $request->chasis_number;
            $vehicle->model          = $request->model;
            $vehicle->year           = $request->year;
            $vehicle->brand          = $request->brand;
            $vehicle->color          = $request->color;
            $vehicle->save();
            return $vehicle;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    public function update($id, $request)
    {
        try {
            $vehicle                 = Vehicle::find($id);
            $vehicle->plate_no       = $request->plate_no;
            $vehicle->chasis_number  = $request->chasis_number;
            $vehicle->model          = $request->model;
            $vehicle->year           = $request->year;
            $vehicle->brand          = $request->brand;
            $vehicle->color          = $request->color;
            $vehicle->save();
            return true;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    public function delete($id){
        return Vehicle::destroy($id);
    }



}
