<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ChargeLine extends Model
{
    use LogsActivity;

    protected $fillable = [
        'shipment_id',
        'charge_type',
        'description',
        'amount',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('charge_line')
            ->logOnly(['shipment_id', 'charge_type', 'amount'])
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName} charge line");
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shipment::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('charge_type', $type);
    }

    public function scopeByShipment($query, int $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }
}
