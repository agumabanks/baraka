<?php

namespace App\Models;

use App\Enums\DriverTimeLogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Driver;
use App\Models\DriverRoster;

class DriverTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'roster_id',
        'log_type',
        'logged_at',
        'latitude',
        'longitude',
        'source',
        'metadata',
    ];

    protected $casts = [
        'log_type' => DriverTimeLogType::class,
        'logged_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'metadata' => 'array',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function roster(): BelongsTo
    {
        return $this->belongsTo(DriverRoster::class, 'roster_id');
    }
}
