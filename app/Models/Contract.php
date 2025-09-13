<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_id','name','start_date','end_date','rate_card_id','sla_json','status','notes'
    ];

    protected $casts = [
        'sla_json' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
}

