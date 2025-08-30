<?php
namespace App\Repositories\AssetCategory;
use App\Models\Backend\Assetcategory;
use App\Repositories\AssetCategory\AssetCategoryInterface;

class AssetCategoryRepository implements AssetCategoryInterface{
    public function all(){
        return Assetcategory::orderBy('position','asc')->paginate(10);
    }

    public function get($id){
        return Assetcategory::find($id);
    }

    public function store($request){
        try {
            $assetcategory               = new Assetcategory();
            $assetcategory->title        = $request->title;
            $assetcategory->position     = $request->position;
            $assetcategory->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($request)
    {

        try {
            $assetcategory               =  Assetcategory::find($request->id);
            $assetcategory->title        = $request->title;
            $assetcategory->position     = $request->position;
            $assetcategory->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        return Assetcategory::destroy($id);
    }
}
