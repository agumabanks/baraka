<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Manifest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'number', 'mode', 'type', 'carrier_id', 'driver_id', 'vehicle_id', 'departure_at', 'arrival_at', 'origin_branch_id', 'destination_branch_id', 'legs_json', 'bags_json', 'status', 'docs',
    ];

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_DEPARTED = 'departed';
    const STATUS_ARRIVED = 'arrived';

    protected $casts = [
        'legs_json' => 'array',
        'bags_json' => 'array',
        'docs' => 'array',
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Backend\Vehicle::class);
    }

    public function items()
    {
        return $this->hasMany(ManifestItem::class);
    }

    public function originBranch()
    {
        return $this->belongsTo(Backend\Branch::class, 'origin_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Backend\Branch::class, 'destination_branch_id');
    }
}
