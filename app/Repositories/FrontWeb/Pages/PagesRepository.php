<?php
namespace App\Repositories\FrontWeb\Pages;

use App\Models\Backend\FrontWeb\Page;
use App\Repositories\FrontWeb\Pages\PagesInterface;
use Illuminate\Support\Facades\Auth;

class PagesRepository implements PagesInterface{
    
    public function all(){
        return Page::paginate(10);
    }
    public function get($page){
        return Page::where('page',$page)->active()->first();
    }
    public function getFind($id){
        return Page::find($id);
    }
 
    public function update($id,$request){
        try {
            $blog              = $this->getFind($id);
            $blog->title       = $request->title;  
            $blog->description = $request->description; 
            $blog->status      = $request->status; 
            $blog->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    } 
}