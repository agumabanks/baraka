<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchManager extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
        'business_name',
        'current_balance',
        'cod_charges',
        'payment_info',
        'settlement_config',
        'metadata',
        'status',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'cod_charges' => 'array',
        'payment_info' => 'array',
        'settlement_config' => 'array',
        'metadata' => 'array',
        'status' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(UnifiedBranch::class, 'branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
