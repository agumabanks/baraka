<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accident extends Model
{
    use HasFactory;
    protected $casts = ['multi_documents'=>'array'];

    public function asset (){
        return $this->belongsTo(Asset::class,'asset_id','id');
    }

    public function driver(){
        return $this->belongsTo(DeliveryMan::class,'driver_responsible','id');
    }

    public function getDocumentsAttribute()
    {
        $documents = [];
        $uploads = Upload::whereIn('id',$this->multi_documents?? [])->get();

        foreach ($uploads as $key => $upload) {
            if ($upload->original && !empty($upload->original['original']) && file_exists(public_path($upload->original['original']))) {
                 $documents[] = static_asset($upload->original['original']);
            }
        }
        return $documents;
    }

}
