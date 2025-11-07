<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'action_type',
        'resource_type',
        'resource_id',
        'module',
        'old_values',
        'new_values',
        'changed_fields',
        'severity',
        'metadata',
        'transaction_id',
        'occurred_at',
        'is_reversible',
        'reversal_data',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'reversal_data' => 'array',
        'occurred_at' => 'datetime',
        'is_reversible' => 'boolean',
    ];

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get readable action description
     */
    public function getActionDescriptionAttribute(): string
    {
        $actionMap = [
            'create' => 'created',
            'read' => 'viewed',
            'update' => 'updated',
            'delete' => 'deleted',
            'login' => 'logged in',
            'logout' => 'logged out',
            'activate' => 'activated',
            'deactivate' => 'deactivated',
            'export' => 'exported',
            'import' => 'imported',
        ];

        return $actionMap[$this->action_type] ?? $this->action_type;
    }

    /**
     * Get severity color for UI
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'error' => 'orange',
            'warning' => 'yellow',
            'info' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get formatted timestamp
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->occurred_at->format('Y-m-d H:i:s');
    }

    /**
     * Get user-readable resource name
     */
    public function getResourceNameAttribute(): string
    {
        $resourceMap = [
            'user' => 'User',
            'parcel' => 'Parcel',
            'contract' => 'Contract',
            'pricing' => 'Pricing',
            'merchant' => 'Merchant',
            'shipment' => 'Shipment',
            'delivery' => 'Delivery',
            'payment' => 'Payment',
            'report' => 'Report',
            'setting' => 'Setting',
            'role' => 'Role',
            'permission' => 'Permission',
        ];

        return $resourceMap[$this->resource_type] ?? ucfirst($this->resource_type);
    }

    /**
     * Check if log can be reversed
     */
    public function canBeReversed(): bool
    {
        return $this->is_reversible && in_array($this->action_type, ['create', 'update', 'delete']);
    }

    /**
     * Get changed fields summary
     */
    public function getChangedFieldsSummaryAttribute(): string
    {
        if (!$this->changed_fields) {
            return 'No fields changed';
        }

        $count = count($this->changed_fields);
        if ($count === 1) {
            return "Field changed: {$this->changed_fields[0]}";
        }

        return "{$count} fields changed: " . implode(', ', array_slice($this->changed_fields, 0, 3)) . 
               ($count > 3 ? " and {$count} more" : '');
    }

    /**
     * Scope for user filtering
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for resource filtering
     */
    public function scopeForResource($query, $type, $id = null)
    {
        $query = $query->where('resource_type', $type);
        if ($id) {
            $query->where('resource_id', $id);
        }
        return $query;
    }

    /**
     * Scope for action type filtering
     */
    public function scopeForAction($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope for severity filtering
     */
    public function scopeForSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for critical logs
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}