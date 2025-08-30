<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssign extends Model
{
    use HasFactory;

    protected $fillable  = [
    'asset_id',
    'driver_id',
    'from_date',
    'to_date'];

    public function driver(){
        return $this->belongsTo(DeliveryMan::class,'driver_id','id');
    }


}
