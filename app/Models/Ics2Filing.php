<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Ics2Filing extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'shipment_id','transport_leg_id','mode','ens_ref','status','lodged_at','response_json'
    ];

    protected $casts = [
        'response_json' => 'array',
        'lodged_at' => 'datetime',
    ];
}

