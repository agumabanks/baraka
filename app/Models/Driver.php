<?php

namespace App\Models;

use App\Enums\DriverStatus;
use App\Enums\EmploymentStatus;
use App\Models\Backend\Branch;
use App\Models\Backend\Vehicle as BackendVehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'branch_id',
        'code',
        'name',
        'phone',
        'email',
        'status',
        'employment_status',
        'license_number',
        'license_expiry',
        'vehicle_id',
        'documents',
        'metadata',
        'onboarded_at',
        'offboarded_at',
    ];

    protected $casts = [
        'status' => DriverStatus::class,
        'license_expiry' => 'date',
        'documents' => 'array',
        'metadata' => 'array',
        'onboarded_at' => 'datetime',
        'offboarded_at' => 'datetime',
        'employment_status' => EmploymentStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(BackendVehicle::class, 'vehicle_id');
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(DriverRoster::class, 'driver_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(DriverTimeLog::class, 'driver_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE')
            ->whereNull('offboarded_at');
    }

    public function isOnDuty(): bool
    {
        return $this->rosters()
            ->where('status', 'IN_PROGRESS')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->exists();
    }
}
