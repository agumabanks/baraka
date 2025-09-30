<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Claim extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'shipment_id', 'type', 'description', 'amount_claimed', 'evidence', 'status', 'settled_amount',
    ];

    protected $casts = [
        'evidence' => 'array',
    ];
}
