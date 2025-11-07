<?php

namespace App\Models;

use App\Enums\RosterStatus;
use App\Models\Backend\Branch as BackendBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Driver;
use App\Models\DriverTimeLog;

class DriverRoster extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'branch_id',
        'shift_type',
        'start_time',
        'end_time',
        'status',
        'planned_hours',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => RosterStatus::class,
        'metadata' => 'array',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BackendBranch::class, 'branch_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(DriverTimeLog::class, 'roster_id');
    }
}
