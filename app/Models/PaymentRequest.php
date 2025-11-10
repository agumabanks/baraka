<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    use HasFactory;

    protected $table = 'payment_requests';

    protected $fillable = [
        'branch_manager_id',
        'amount',
        'status',
        'requested_by',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function branchManager(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Backend\BranchManager::class, 'branch_manager_id');
    }
}
