<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessibilityTestQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'page_url',
        'test_type',
        'test_config',
        'status',
        'error_message',
        'scheduled_at',
        'started_at',
        'completed_at',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'test_config' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get test status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'running' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get queue position based on priority and scheduled time
     */
    public function getQueuePositionAttribute(): int
    {
        return self::where(function ($query) {
                $query->where('status', 'pending')
                      ->where('priority', '>', $this->priority);
            })
            ->orWhere(function ($query) {
                $query->where('status', 'pending')
                      ->where('priority', $this->priority)
                      ->where('scheduled_at', '<', $this->scheduled_at);
            })
            ->count() + 1;
    }

    /**
     * Check if test is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at < now();
    }

    /**
     * Scope for pending tests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for overdue tests
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('scheduled_at', '<', now());
    }
}