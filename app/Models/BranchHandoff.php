<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchHandoff extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'origin_branch_id',
        'dest_branch_id',
        'requested_by',
        'approved_by',
        'status',
        'notes',
        'approved_at',
        'expected_hand_off_at',
        'handoff_completed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expected_hand_off_at' => 'datetime',
        'handoff_completed_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Backend\Branch::class, 'origin_branch_id');
    }

    public function destBranch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Backend\Branch::class, 'dest_branch_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
