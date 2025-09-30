<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Manifest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'number', 'mode', 'carrier_id', 'departure_at', 'arrival_at', 'origin_branch_id', 'destination_branch_id', 'legs_json', 'bags_json', 'status', 'docs',
    ];

    protected $casts = [
        'legs_json' => 'array',
        'bags_json' => 'array',
        'docs' => 'array',
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
    ];
}
