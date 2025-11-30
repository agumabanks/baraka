<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'sequence',
        'stop_type',
        'location_name',
        'address',
        'latitude',
        'longitude',
        'contact_person',
        'contact_phone',
        'status',
        'planned_arrival',
        'actual_arrival',
        'completed_at',
        'shipment_ids',
        'items_count',
        'recipient_name',
        'recipient_signature_path',
        'photo_paths',
        'delivery_notes',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'planned_arrival' => 'datetime',
        'actual_arrival' => 'datetime',
        'completed_at' => 'datetime',
        'shipment_ids' => 'array',
        'photo_paths' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'items_count' => 'integer',
        'sequence' => 'integer',
    ];

    // Relationships

    public function trip(): BelongsTo
    {
        return $this->belongsTo(VehicleTrip::class, 'trip_id');
    }

    // Business Logic

    /**
     * Mark stop as arrived
     */
    public function markArrived(): void
    {
        $this->update([
            'status' => 'arrived',
            'actual_arrival' => now(),
        ]);
    }

    /**
     * Complete stop with POD
     */
    public function complete(array $podData = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'recipient_name' => $podData['recipient_name'] ?? null,
            'recipient_signature_path' => $podData['signature_path'] ?? null,
            'photo_paths' => $podData['photo_paths'] ?? null,
            'delivery_notes' => $podData['notes'] ?? null,
        ]);

        // Update trip completed stops count
        $this->trip->increment('completed_stops');

        activity()
            ->performedOn($this)
            ->log('Stop completed');
    }

    /**
     * Mark stop as failed
     */
    public function markFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $this->notes . "\nFailed: " . $reason,
        ]);

        activity()
            ->performedOn($this)
            ->withProperties(['reason' => $reason])
            ->log('Stop failed');
    }

    /**
     * Check if POD is captured
     */
    public function getHasPodAttribute(): bool
    {
        return !empty($this->recipient_name) && 
               (!empty($this->recipient_signature_path) || !empty($this->photo_paths));
    }

    /**
     * Get delay in minutes
     */
    public function getDelayMinutesAttribute(): ?int
    {
        if (!$this->planned_arrival || !$this->actual_arrival) {
            return null;
        }

        $delay = $this->actual_arrival->diffInMinutes($this->planned_arrival, false);
        return $delay > 0 ? $delay : 0;
    }
}
