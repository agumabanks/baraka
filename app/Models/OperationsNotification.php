<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OperationsNotification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_uuid',
        'type',
        'category',
        'title',
        'message',
        'severity',
        'priority',
        'data',
        'action_data',
        'status',
        'requires_action',
        'is_dismissed',
        'channels',
        'sent_at',
        'delivered_at',
        'read_at',
        'dismissed_at',
        'user_id',
        'branch_id',
        'recipient_role',
        'shipment_id',
        'worker_id',
        'asset_id',
        'related_entity_type',
        'related_entity_id',
        'created_by',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'action_data' => 'array',
        'channels' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'requires_action' => 'boolean',
        'is_dismissed' => 'boolean',
        'priority' => 'integer',
    ];

    protected $appends = ['age_in_hours', 'is_read', 'is_urgent'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            if (empty($notification->notification_uuid)) {
                $notification->notification_uuid = (string) Str::uuid();
            }
        });
    }

    // ==================== Relationships ====================

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Backend\Branch::class, 'branch_id');
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function worker()
    {
        return $this->belongsTo(\App\Models\Backend\BranchWorker::class, 'worker_id');
    }

    public function asset()
    {
        return $this->belongsTo(\App\Models\Backend\Asset::class, 'asset_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function relatedEntity()
    {
        return $this->morphTo(__FUNCTION__, 'related_entity_type', 'related_entity_id');
    }

    // ==================== Scopes ====================

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForRole($query, $role)
    {
        return $query->where('recipient_role', $role);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at')->where('is_dismissed', false);
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeRequiresAction($query)
    {
        return $query->where('requires_action', true)->where('is_dismissed', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', '>=', $priority);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical')->orWhere('priority', '>=', 4);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // ==================== Accessors ====================

    public function getAgeInHoursAttribute()
    {
        return $this->created_at->diffInHours(now());
    }

    public function getIsReadAttribute()
    {
        return !is_null($this->read_at);
    }

    public function getIsUrgentAttribute()
    {
        return $this->severity === 'critical' || $this->priority >= 4 || $this->requires_action;
    }

    // ==================== Methods ====================

    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update([
                'read_at' => now(),
                'status' => 'read',
            ]);
        }

        return $this;
    }

    public function markAsDelivered()
    {
        if (is_null($this->delivered_at)) {
            $this->update([
                'delivered_at' => now(),
                'status' => 'delivered',
            ]);
        }

        return $this;
    }

    public function markAsSent()
    {
        if (is_null($this->sent_at)) {
            $this->update([
                'sent_at' => now(),
                'status' => 'sent',
            ]);
        }

        return $this;
    }

    public function dismiss()
    {
        $this->update([
            'is_dismissed' => true,
            'dismissed_at' => now(),
        ]);

        return $this;
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        // Add formatted timestamps
        $array['created_at_human'] = $this->created_at->diffForHumans();
        $array['age_hours'] = $this->age_in_hours;
        
        return $array;
    }

    // ==================== Static Helpers ====================

    public static function createNotification(array $data)
    {
        return self::create(array_merge([
            'notification_uuid' => (string) Str::uuid(),
            'category' => 'operational',
            'severity' => 'medium',
            'priority' => 3,
            'status' => 'pending',
            'channels' => ['websocket'],
        ], $data));
    }

    public static function createExceptionNotification(Shipment $shipment, array $exceptionData)
    {
        return self::createNotification([
            'type' => 'exception.created',
            'category' => 'operational',
            'title' => 'New Exception Created',
            'message' => "Exception: {$exceptionData['exception_type']} for shipment {$shipment->tracking_number}",
            'severity' => $exceptionData['severity'] ?? 'medium',
            'priority' => $exceptionData['priority'] ?? 3,
            'data' => $exceptionData,
            'requires_action' => true,
            'shipment_id' => $shipment->id,
            'branch_id' => $shipment->origin_branch_id ?? $shipment->dest_branch_id,
            'channels' => ['websocket', 'push'],
        ]);
    }

    public static function createAlert(string $alertType, array $alertData)
    {
        return self::createNotification([
            'type' => $alertType,
            'category' => 'alert',
            'title' => $alertData['title'] ?? 'Operational Alert',
            'message' => $alertData['message'] ?? 'Alert requires attention',
            'severity' => $alertData['severity'] ?? 'medium',
            'priority' => $alertData['priority'] ?? 3,
            'data' => $alertData,
            'requires_action' => $alertData['requires_action'] ?? false,
            'channels' => $alertData['channels'] ?? ['websocket'],
        ]);
    }

    public static function bulkMarkAsRead(array $notificationIds, $userId)
    {
        return self::whereIn('id', $notificationIds)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'status' => 'read',
            ]);
    }

    public static function getUnreadCountForUser($userId)
    {
        return self::forUser($userId)->unread()->count();
    }

    public static function cleanupOldNotifications($daysOld = 90)
    {
        return self::where('created_at', '<', now()->subDays($daysOld))
            ->where('requires_action', false)
            ->forceDelete();
    }
}
