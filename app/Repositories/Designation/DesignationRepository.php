<?php

namespace App\Repositories\Designation;

use App\Models\Backend\Designation;

class DesignationRepository implements DesignationInterface
{
    public function all()
    {
        return Designation::orderByDesc('id')->paginate(10);
    }

    public function get($id)
    {
        return Designation::find($id);
    }

    public function store($request)
    {
        try {
            $designation = new Designation;
            $designation->title = $request->title;
            $designation->status = $request->status;
            $designation->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $designation = Designation::find($id);
            $designation->title = $request->title;
            $designation->status = $request->status;
            $designation->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id)
    {
        return Designation::destroy($id);
    }
}
