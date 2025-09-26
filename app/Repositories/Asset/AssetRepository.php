<?php

namespace App\Repositories\Asset;

use App\Models\Backend\Asset;
use App\Repositories\Asset\AssetInterface;
use App\Models\Backend\Hub;
use App\Models\Backend\Assetcategory;
use App\Models\Backend\Upload;
use App\Repositories\Vehicles\VehiclesInterface;
use Illuminate\Support\Facades\Auth;

class AssetRepository implements AssetInterface
{

    protected $vehicleRepo;
    public function __construct(VehiclesInterface $vehicleRepo)
    {
        $this->vehicleRepo = $vehicleRepo;
    }
    public function all()
    {
        return Asset::orderBy('id', 'desc')->paginate(10);
    }

    // get all rows in Hub model
    public function hubs()
    {
        return Hub::orderBy('name')->get();
    }

    // get all rows in assetcategory model
    public function assetcategorys()
    {
        return Assetcategory::orderBy('title')->get();
    }

    public function get($id)
    {
        return Asset::find($id);
    }

    // All request data store in NewsOffer tabel.
    public function store($request)
    {

        try {
            $vehicle=$this->vehicleRepo->store($request);
            $asset                     = new Asset();
            $asset->author             = Auth::user()->id;
            $asset->name               = $request->name;
            $asset->asset_type         = $request->asset_type;
            $asset->vehicle_id         = $vehicle->id;
            $asset->assetcategory_id   = $request->assetcategory_id;
            $asset->amount             = currencyAmountDevide($request->amount);
            $asset->purchase_date               = $request->purchase_date;
            if (isset($request->registration_documents) && $request->registration_documents != null) {
                $asset->registration_documents  = $this->fileUpload('', $request->registration_documents, 'uploads/registration_documents');
            }
            $asset->yearly_depreciation_value   = $request->yearly_depreciation_value;
            $asset->insurance_status            = $request->insurance_status;
            if (isset($request->insurance_documents) && $request->insurance_documents != null) {
                $asset->insurance_documents     = $this->fileUpload('', $request->insurance_documents, 'uploads/insurance_documents');
            }

            $asset->registration_date         = $request->registration_date;
            $asset->registration_expiry_date  = $request->registration_expiry_date;

            $asset->insurance_registration    = $request->insurance_registration;
            $asset->insurance_expiry_date       = $request->insurance_expiry_date;

            $asset->insurance_amount            = currencyAmountDevide($request->insurance_amount);
            $asset->maintenance_schedule        = $request->maintenance_schedule;
            $asset->description                 = $request->description;
            $asset->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // All request data update in
    public function update($request)
    {
        try {
            $asset                     = Asset::find($request->id);
            $asset->author             = Auth::user()->id;
            $asset->name               = $request->name;
            $asset->asset_type         = $request->asset_type;
            $asset->assetcategory_id   = $request->assetcategory_id;
            $asset->amount             = currencyAmountDevide($request->amount);
            $asset->purchase_date               = $request->purchase_date;
            if (isset($request->registration_documents) && $request->registration_documents != null) {
                $asset->registration_documents  = $this->fileUpload($asset->registration_documents, $request->registration_documents, 'uploads/registration_documents');
            }
            $asset->yearly_depreciation_value   = $request->yearly_depreciation_value;
            $asset->insurance_status            = $request->insurance_status;
            if (isset($request->insurance_documents) && $request->insurance_documents != null) {
                $asset->insurance_documents     = $this->fileUpload($asset->insurance_documents, $request->insurance_documents, 'uploads/insurance_documents');
            }

            $asset->registration_date         = $request->registration_date;
            $asset->registration_expiry_date  = $request->registration_expiry_date;

            $asset->insurance_registration    = $request->insurance_registration;
            $asset->insurance_expiry_date       = $request->insurance_expiry_date;
            $asset->insurance_amount            = currencyAmountDevide($request->insurance_amount);
            $asset->maintenance_schedule        = $request->maintenance_schedule;
            $asset->description                 = $request->description;
            $asset->save();
            $this->vehicleRepo->update($asset->vehicle_id,$request);
            return true;
        } catch (\Exception $e) {

            return false;
        }
    }
    // Delete single row in  Model
    public function delete($id)
    {
        return Asset::destroy($id);
    }

    public function fileUpload($image_id = '', $image, $file_path)
    {
        try {
            $image_name = '';
            if (!blank($image)) {
                $destinationPath       = public_path($file_path);
                $merchantImage         = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $merchantImage);
                $image_name            = $file_path . '/' . $merchantImage;
            }

            if (blank($image_id)) {
                $upload           = new Upload();
            } else {
                $upload           = Upload::find($image_id);
                if (file_exists($upload->original)) {
                    unlink($upload->original);
                }
            }

            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;
        } catch (\Exception $e) {
            return false;
        }
    }
}
