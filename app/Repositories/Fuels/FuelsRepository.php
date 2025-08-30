<?php
namespace App\Repositories\Fuels;
use App\Models\Backend\Fuel;
use App\Models\Backend\Upload;

class FuelsRepository implements FuelsInterface{
    public function all(){
        return Fuel::orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return Fuel::find($id);
    }

    public function store($request){
        try {
            $fuel                 = new Fuel();
            $fuel->asset_id       = $request->asset_id;
            $fuel->fuel_type      = $request->fuel_type;
            if($request->invoice_of_fuel):
                $fuel->invoice_of_fuel =  $this->fileUpload('', $request->invoice_of_fuel, 'uploads/users');
            endif;
            $fuel->amount           = currencyAmountDevide($request->amount);
            $fuel->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $fuel                   = Fuel::find($id);
            $fuel->asset_id         = $request->asset_id;
            $fuel->fuel_type        = $request->fuel_type;
            if($request->invoice_of_fuel):
                $fuel->invoice_of_fuel =  $this->fileUpload($fuel->invoice_of_fuel, $request->invoice_of_fuel, 'uploads/users');
            endif;
            $fuel->amount           = currencyAmountDevide($request->amount);
            $fuel->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        return Fuel::destroy($id);
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
