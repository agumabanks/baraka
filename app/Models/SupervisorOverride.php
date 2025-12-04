<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorOverride extends Model
{
    protected $fillable = [
        'shipment_id',
        'action_type',
        'requested_by',
        'approved_by',
        'reason',
        'request_data',
        'approved_data',
        'status',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'approved_data' => 'array',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const ACTION_DISCOUNT = 'discount';
    public const ACTION_CANCEL = 'cancel';
    public const ACTION_BACKDATE = 'backdate';
    public const ACTION_REPRINT = 'reprint';
    public const ACTION_PRICE_OVERRIDE = 'price_override';
    public const ACTION_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function approve(int $approverId, ?array $data = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_data' => $data,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $approverId, ?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approverId,
            'approved_data' => ['rejection_reason' => $reason],
            'approved_at' => now(),
        ]);
    }

    public static function requestOverride(
        string $actionType,
        int $requesterId,
        string $reason,
        ?int $shipmentId = null,
        ?array $requestData = null
    ): self {
        return static::create([
            'shipment_id' => $shipmentId,
            'action_type' => $actionType,
            'requested_by' => $requesterId,
            'reason' => $reason,
            'request_data' => $requestData,
            'status' => self::STATUS_PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);
    }
}
