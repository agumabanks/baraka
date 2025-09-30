<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashOffice extends Model
{
    use HasFactory;

    protected $table = 'cash_office_days';

    protected $fillable = [
        'branch_id', 'business_date', 'cod_collected', 'cash_on_hand', 'banked_amount', 'variance', 'submitted_by_id', 'submitted_at',
    ];

    protected $casts = [
        'business_date' => 'date',
        'submitted_at' => 'datetime',
    ];
}
