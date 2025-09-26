<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class DangerousGood extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'shipment_id','un_number','dg_class','packing_group','proper_shipping_name','net_qty','pkg_type','status','docs'
    ];

    protected $casts = [
        'docs' => 'array',
    ];
}

