<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CodReceipt extends Model
{
    use LogsActivity;

    protected $fillable = [
        'shipment_id',
        'amount',
        'currency',
        'method',
        'receipt_image_path',
        'collected_by',
        'collected_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'collected_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('cod_receipt')
            ->logOnly(['shipment_id', 'amount', 'method', 'collected_by'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} COD receipt");
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shipment::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'collected_by');
    }

    // Scopes
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    public function scopeByCollector($query, int $userId)
    {
        return $query->where('collected_by', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('collected_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getReceiptImageUrlAttribute(): ?string
    {
        return $this->receipt_image_path ? asset($this->receipt_image_path) : null;
    }
}
