<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchWorker extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
        'role',
        'permissions',
        'work_schedule',
        'hourly_rate',
        'assigned_at',
        'unassigned_at',
        'notes',
        'metadata',
        'status',
    ];

    protected $casts = [
        'permissions' => 'array',
        'work_schedule' => 'array',
        'hourly_rate' => 'decimal:2',
        'assigned_at' => 'date',
        'unassigned_at' => 'date',
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

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isDispatcher(): bool
    {
        return $this->role === 'dispatcher';
    }
}
