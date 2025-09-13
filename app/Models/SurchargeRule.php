<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class SurchargeRule extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code','name','trigger','rate_type','amount','currency','applies_to','active_from','active_to','active'
    ];

    protected $casts = [
        'applies_to' => 'array',
        'active_from' => 'date',
        'active_to' => 'date',
        'active' => 'boolean',
    ];
}

