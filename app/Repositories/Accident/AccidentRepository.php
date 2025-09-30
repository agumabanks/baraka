<?php

namespace App\Repositories\Accident;

use App\Models\Backend\Accident;
use App\Models\Backend\Upload;

class AccidentRepository implements AccidentInterface
{
    public function all()
    {
        return Accident::orderByDesc('id')->paginate(10);
    }

    public function get($id)
    {
        return Accident::find($id);
    }

    public function store($request)
    {
        try {
            $accident = new Accident;
            $accident->asset_id = $request->asset_id;
            $accident->date_of_accident = $request->date_of_accident;
            $accident->driver_responsible = $request->driver_responsible;
            $accident->cost_of_repair = currencyAmountDevide($request->cost_of_repair);
            $accident->spare_parts = $request->spare_parts;
            if ($request->multi_documents) {
                $documents = [];
                foreach ($request->multi_documents as $document) {
                    $documents[] = $this->fileUpload('', $document, 'uploads/users');
                }
                $accident->multi_documents = $documents;
            }
            $accident->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $accident = Accident::find($id);
            $accident->asset_id = $request->asset_id;
            $accident->date_of_accident = $request->date_of_accident;
            $accident->driver_responsible = $request->driver_responsible;
            $accident->cost_of_repair = currencyAmountDevide($request->cost_of_repair);
            $accident->spare_parts = $request->spare_parts;
            if ($request->multi_documents) {
                $documents = [];
                foreach ($request->multi_documents as $document) {
                    $documents[] = $this->fileUpload('', $document, 'uploads/users');
                }
                $accident->multi_documents = $documents;
            }
            $accident->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id)
    {
        return Accident::destroy($id);
    }

    public function fileUpload($image_id, $image, $file_path)
    {
        try {
            $image_name = '';
            if (! blank($image)) {
                $destinationPath = public_path($file_path);
                $merchantImage = date('YmdHis').random_int(111, 999).'.'.$image->getClientOriginalExtension();
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
