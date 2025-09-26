<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lane extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_zone_id','dest_zone_id','mode','std_transit_days','dim_divisor','eawb_required'
    ];

    public function origin()
    {
        return $this->belongsTo(Zone::class, 'origin_zone_id');
    }

    public function destination()
    {
        return $this->belongsTo(Zone::class, 'dest_zone_id');
    }
}

