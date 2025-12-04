<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentAudit extends Model
{
    protected $fillable = [
        'shipment_id',
        'event_type',
        'old_values',
        'new_values',
        'reason',
        'created_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_STATUS_CHANGED = 'status_changed';
    public const EVENT_DISCOUNT_APPLIED = 'discount_applied';
    public const EVENT_LABEL_PRINTED = 'label_printed';
    public const EVENT_LABEL_REPRINTED = 'label_reprinted';
    public const EVENT_PAYMENT_RECEIVED = 'payment_received';
    public const EVENT_CANCELLED = 'cancelled';
    public const EVENT_PRICE_OVERRIDE = 'price_override';

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForShipment($query, int $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }

    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public static function log(
        int $shipmentId,
        string $eventType,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?int $userId = null
    ): self {
        return static::create([
            'shipment_id' => $shipmentId,
            'event_type' => $eventType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'created_by' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
