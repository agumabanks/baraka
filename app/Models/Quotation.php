<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Quotation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_id','origin_branch_id','destination_country','service_type','pieces','weight_kg',
        'volume_cm3','dim_factor','base_charge','surcharges_json','total_amount','currency','status',
        'valid_until','pdf_path','created_by_id'
    ];

    protected $casts = [
        'surcharges_json' => 'array',
        'valid_until' => 'date',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
}

