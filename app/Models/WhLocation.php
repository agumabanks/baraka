<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id','code','type','capacity','status'
    ];
}

