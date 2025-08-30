<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Hub\StoreHubRequest;
use App\Http\Requests\Hub\UpdateHubRequest;
use App\Models\Backend\Hub;
use App\Models\Backend\Parcel;
use App\Repositories\Hub\HubInterface;
use Brian2694\Toastr\Facades\Toastr;
class HubController extends Controller
{
    protected $repo;
    public function __construct(HubInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $hubs = $this->repo->all();
        return view('backend.hub.index',compact('hubs','request'));
    }
    public function filter(Request $request)
    {
        $hubs = $this->repo->filter($request);
        return view('backend.hub.index',compact('hubs','request'));
    }

    public function create()
    {
        return view('backend.hub.create');
    }

    public function store(StoreHubRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('hub.added_msg'),__('message.success'));
            return redirect()->route('hubs.index');
        }else{
            Toastr::error(__('hub.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $hub = $this->repo->get($id);
        return view('backend.hub.edit',compact('hub'));
    }

    public function update(UpdateHubRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success(__('hub.update_msg'),__('message.success'));
            return redirect()->route('hubs.index');
        }else{
            Toastr::error(__('hub.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('hub.delete_msg'),__('message.success'));
        return back();
    }


    public function view(Request $request,$id){

        $data['parcels']                = $this->repo->parcelFilter($request,$id)->paginate(15);
        $data['t_parcels']              = $this->repo->parcelFilter($request,$id)->get();
        $data['total_parcels']          = $data['t_parcels']->count();
        $data['total_cash_collection']  = $data['t_parcels']->sum('cash_collection');
        $data['total_delivered_cash_collection']           = $this->repo->parcelFilter($request,$id)->where('status',ParcelStatus::DELIVERED)->get()->sum('cash_collection');
        $data['total_partials_delivered_cash_collection']  = $this->repo->parcelFilter($request,$id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->get()->sum('cash_collection');
        $data['total_in_transit_cash_collection']          = $this->repo->parcelFilter($request,$id)->whereNotIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->get()->sum('cash_collection');
        $data['total_delivery_charges']  = $data['t_parcels']->sum('delivery_charge');
        $data['total_vat_amount']        = $data['t_parcels']->sum('vat_amount');
        $data['parcelsGrouped']          = $data['t_parcels']->groupBy('status');
        return view('backend.hub.view',compact('data','request','id'));
    }


    
    public function parcelHubs(Request $request)
    {
  
        $AllHubs      = Hub::where('status',Status::ACTIVE)->get();
        $mapParcels = [];
        
        if (!blank($AllHubs)) {
            
            foreach ($AllHubs as $key => $hub) {
                $hubParcels = Parcel::where('hub_id',$hub->id)->get();
              
                $mapParcels[$key]['hubname']     = $hub->name;
                $mapParcels[$key]['hubphone']    = $hub->phone;
                $mapParcels[$key]['hubaddress']  = $hub->address;
                $mapParcels[$key]['image']    = '#';
                $mapParcels[$key]['lat']              = $hub->hub_lat;
                $mapParcels[$key]['long']             = $hub->hub_long;
                $mapParcels[$key]['customer_name']    = 'asdf';
                $mapParcels[$key]['customer_address'] = 'asdf';
                $mapParcels[$key]['customer_phone']   = '0000000';
                $mapParcels[$key]['merchant_business_name'] = 'asdf';
                $mapParcels[$key]['merchant_phone']   = '000000';
                $mapParcels[$key]['merchant_address'] = 'asdf';
                $mapParcels[$key]['current_payable']  = '50';
                $mapParcels[$key]['tracking_id']      = $this->hubtrackinIds($hubParcels);
                $mapParcels[$key]['url']              = route('parcel.index');
            }
        }

        return view('backend.hub.hubs-parcel-map', compact('mapParcels'));
    }


    
    function hubtrackinIds($hubParcels)
    {

        $ss = 1;
        $trackingids =  '';
        $ee          =  $hubParcels;
        $trackingids .=  'Total Parcels: ' . $ee->count() . ', ';
        foreach ($ee as $key => $parcel) {
            $trackingids .= @$parcel->tracking_id;
            if ($ee->count() > $ss) :
                $ss++;
                $trackingids    .= ', ';
            elseif ($ee->count() == $ss) :
                $ss = 1;
            endif;
        }
        return $trackingids;
    }




}
