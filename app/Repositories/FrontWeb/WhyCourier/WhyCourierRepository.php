<?php

namespace App\Repositories\FrontWeb\WhyCourier;

use App\Models\Backend\FrontWeb\WhyCourier;
use App\Models\Backend\Upload;
use Illuminate\Support\Facades\File;

class WhyCourierRepository implements WhyCourierInterface
{
    public function get()
    {
        return WhyCourier::orderBy('position', 'asc')->paginate(10);
    }

    public function getAll()
    {
        return WhyCourier::with('upload')->active()->orderBy('position', 'asc')->get();
    }

    public function getFind($id)
    {
        return WhyCourier::find($id);
    }

    public function store($request)
    {
        try {
            $whycourier = new WhyCourier;
            $whycourier->title = $request->title;
            if ($request->image) {
                $whycourier->image_id = $this->imageStoreUpdate($whycourier->image_id, $request->image);
            }
            $whycourier->position = $request->position;
            $whycourier->status = $request->status;
            $whycourier->save();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $whycourier = $this->getFind($id);
            $whycourier->title = $request->title;
            if ($request->image) {
                $whycourier->image_id = $this->imageStoreUpdate($whycourier->image_id, $request->image);
            }
            $whycourier->position = $request->position;
            $whycourier->status = $request->status;
            $whycourier->save();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete($id)
    {
        return WhyCourier::destroy($id);
    }

    // Image Store in Upload Model
    public function imageStoreUpdate($file_id, $file)
    {

        try {
            $file_name = '';
            if (! blank($file)) {
                if (! File::exists(public_path('uploads/whycourier'))) {
                    File::makeDirectory(public_path('uploads/whycourier'));
                }
                $destinationPath = public_path('uploads/whycourier');
                $img = date('YmdHis').'.'.$file->getClientOriginalExtension();
                $file->move($destinationPath, $img);
                $file_name = 'uploads/whycourier/'.$img;
            }
            if (blank($file_id)) {
                $upload = new Upload;
            } else {
                $upload = Upload::find($file_id);
                if (File::exists(public_path($upload->original))) {
                    unlink(public_path($upload->original));
                }
            }
            $upload->original = $file_name;
            $upload->save();

            return $upload->id;

        } catch (\Exception $e) {
            return null;
        }
    }
}
