<?php
namespace App\Repositories\Department;
use App\Models\Backend\Department;
use App\Repositories\Department\DepartmentInterface;

class DepartmentRepository implements DepartmentInterface{
    public function all(){
        return Department::orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return Department::find($id);
    }

    public function store($request){
        try {
            $department          = new Department();
            $department->title   = $request->title;
            $department->status  = $request->status;
            $department->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $department          = Department::find($id);
            $department->title   = $request->title;
            $department->status  = $request->status;
            $department->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        return Department::destroy($id);
    }
}