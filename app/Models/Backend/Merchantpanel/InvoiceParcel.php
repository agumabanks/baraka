<?php

namespace App\Models\Backend\Merchantpanel;

use App\Models\Backend\Parcel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceParcel extends Model
{
    use HasFactory;

    
    public function parcel(){
        return $this->belongsTo(Parcel::class,'parcel_id','id');
    }

    public function invoice (){
        return $this->belongsTo(Invoice::class,'invoice_id','id');
    }

}
