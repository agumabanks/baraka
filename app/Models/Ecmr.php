<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Ecmr extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'cmr_number','road_carrier','origin_branch_id','destination_branch_id','doc_path','status'
    ];
}

