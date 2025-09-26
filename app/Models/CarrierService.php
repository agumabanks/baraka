<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarrierService extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_id','code','name','requires_eawb'
    ];

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }
}

