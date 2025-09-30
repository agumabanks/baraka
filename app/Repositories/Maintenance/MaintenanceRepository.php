<?php

namespace App\Repositories\Maintenance;

use App\Models\Backend\Maintenance;
use App\Models\Backend\Upload;

class MaintenanceRepository implements MaintenanceInterface
{
    public function all()
    {
        return Maintenance::orderByDesc('id')->paginate(10);
    }

    public function get($id)
    {
        return Maintenance::find($id);
    }

    public function store($request)
    {
        try {
            $maintenance = new Maintenance;
            $maintenance->asset_id = $request->asset_id;
            $maintenance->start_date = $request->start_date;
            $maintenance->end_date = $request->end_date;
            $maintenance->repair_details = $request->repair_details;
            $maintenance->spare_parts_purchased_details = $request->spare_parts_purchased_details;

            if ($request->invoice_of_the_purchases) {
                $maintenance->invoice_of_the_purchases = $this->fileUpload('', $request->invoice_of_the_purchases, 'uploads/users');
            }
            $maintenance->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $maintenance = Maintenance::find($id);
            $maintenance->asset_id = $request->asset_id;
            $maintenance->start_date = $request->start_date;
            $maintenance->end_date = $request->end_date;
            $maintenance->repair_details = $request->repair_details;
            $maintenance->spare_parts_purchased_details = $request->spare_parts_purchased_details;
            if ($request->invoice_of_the_purchases) {
                $maintenance->invoice_of_the_purchases = $this->fileUpload($maintenance->invoice_of_the_purchases, $request->invoice_of_the_purchases, 'uploads/users');
            }
            $maintenance->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id)
    {
        return Maintenance::destroy($id);
    }

    public function fileUpload($image_id, $image, $file_path)
    {
        try {
            $image_name = '';
            if (! blank($image)) {
                $destinationPath = public_path($file_path);
                $merchantImage = date('YmdHis').'.'.$image->getClientOriginalExtension();
                $image->move($destinationPath, $merchantImage);
                $image_name = $file_path.'/'.$merchantImage;
            }

            if (blank($image_id)) {
                $upload = new Upload;
            } else {
                $upload = Upload::find($image_id);
                if (file_exists($upload->original)) {
                    unlink($upload->original);
                }
            }
            $upload->original = $image_name;
            $upload->save();

            return $upload->id;
        } catch (\Exception $e) {
            return false;
        }
    }
}
