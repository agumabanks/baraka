<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'worker_id',
        'shift_date',
        'start_at',
        'end_at',
        'check_in_at',
        'check_out_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Backend\Branch::class, 'branch_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Backend\BranchWorker::class, 'worker_id');
    }
}
