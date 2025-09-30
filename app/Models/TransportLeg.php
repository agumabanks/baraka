<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TransportLeg extends Model
{
    use LogsActivity;

    protected $fillable = [
        'shipment_id',
        'mode',
        'carrier',
        'flight_number',
        'vehicle_number',
        'awb',
        'cmr',
        'depart_at',
        'arrive_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'arrive_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('transport_leg')
            ->logOnly(['shipment_id', 'mode', 'carrier', 'status', 'depart_at', 'arrive_at'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} transport leg");
    }

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shipment::class);
    }

    public function bags(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Bag::class, 'bag_leg', 'leg_id', 'bag_id');
    }

    public function scanEvents()
    {
        return $this->hasMany(ScanEvent::class, 'leg_id');
    }

    // Accessors
    public function getDurationAttribute(): ?int
    {
        if ($this->depart_at && $this->arrive_at) {
            return $this->depart_at->diffInMinutes($this->arrive_at);
        }

        return null;
    }

    public function getIsDelayedAttribute(): bool
    {
        return $this->arrive_at && $this->arrive_at->isPast() && $this->status !== 'ARRIVED';
    }

    // Scopes
    public function scopeByMode($query, string $mode)
    {
        return $query->where('mode', $mode);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['PLANNED', 'DEPARTED']);
    }

    public function scopeDelayed($query)
    {
        return $query->where('arrive_at', '<', now())
            ->where('status', '!=', 'ARRIVED');
    }

    // Business Logic
    public function updateStatus(string $newStatus): void
    {
        $this->update(['status' => $newStatus]);

        // Update related bags status
        if ($newStatus === 'DEPARTED') {
            $this->bags()->update(['status' => 'IN_TRANSIT']);
        } elseif ($newStatus === 'ARRIVED') {
            $this->bags()->update(['status' => 'ARRIVED']);
        }
    }
}
