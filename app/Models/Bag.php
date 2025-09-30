<?php

namespace App\Models;

use App\Models\Backend\Hub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Bag extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'origin_branch_id',
        'dest_branch_id',
        'status',
        'leg_id',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('bag')
            ->logOnly(['code', 'origin_branch_id', 'dest_branch_id', 'status'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} bag");
    }

    // Relationships
    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_branch_id');
    }

    public function destBranch(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'dest_branch_id');
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TransportLeg::class, 'leg_id');
    }

    public function parcels(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Backend\Parcel::class, 'bag_parcel', 'bag_id', 'sscc', 'id', 'sscc');
    }

    public function shipment()
    {
        // Get shipment through parcels
        $parcel = $this->parcels()->first();

        return $parcel ? $parcel->shipment : null;
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class, 'sscc', 'code');
    }

    // Accessors
    public function getParcelCountAttribute(): int
    {
        return $this->parcels()->count();
    }

    public function getTotalWeightAttribute(): float
    {
        return $this->parcels()->sum('weight');
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('origin_branch_id', $branchId)
            ->orWhere('dest_branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['OPEN', 'CLOSED']);
    }

    // Business Logic
    public function addParcel(string $sscc): bool
    {
        if ($this->status !== 'OPEN') {
            return false;
        }

        $parcel = \App\Models\Backend\Parcel::where('sscc', $sscc)->first();
        if (! $parcel) {
            return false;
        }

        $this->parcels()->attach($sscc);

        return true;
    }

    public function removeParcel(string $sscc): bool
    {
        if ($this->status !== 'OPEN') {
            return false;
        }

        $this->parcels()->detach($sscc);

        return true;
    }

    public function close(): bool
    {
        if ($this->status !== 'OPEN' || $this->parcels()->count() === 0) {
            return false;
        }

        $this->update([
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);

        return true;
    }
}
