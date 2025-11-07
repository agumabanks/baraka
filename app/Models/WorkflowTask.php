<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'creator_id',
        'assigned_to',
        'project_id',
        'project_name',
        'stage',
        'status_label',
        'client',
        'tracking_number',
        'due_at',
        'completed_at',
        'last_status_at',
        'tags',
        'metadata',
        'time_tracking',
        'dependencies',
        'attachments',
        'watchers',
        'allowed_transitions',
        'restricted_roles',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_status_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
        'time_tracking' => 'array',
        'dependencies' => 'array',
        'attachments' => 'array',
        'watchers' => 'array',
        'allowed_transitions' => 'array',
        'restricted_roles' => 'array',
    ];

    protected $attributes = [
        'tags' => '[]',
        'metadata' => '{}',
        'time_tracking' => '{}',
        'dependencies' => '[]',
        'attachments' => '[]',
        'watchers' => '[]',
        'allowed_transitions' => '{}',
        'restricted_roles' => '[]',
    ];

    public const STATUSES = [
        'pending',
        'in_progress',
        'testing',
        'awaiting_feedback',
        'completed',
        'delayed',
    ];

    public const PRIORITIES = ['low', 'medium', 'high'];

    public static function defaultTransitions(): array
    {
        return [
            'pending' => ['in_progress', 'testing', 'awaiting_feedback', 'completed', 'delayed'],
            'in_progress' => ['testing', 'awaiting_feedback', 'completed', 'delayed', 'pending'],
            'testing' => ['in_progress', 'awaiting_feedback', 'completed', 'delayed'],
            'awaiting_feedback' => ['in_progress', 'completed', 'delayed'],
            'completed' => ['pending', 'in_progress'],
            'delayed' => ['in_progress', 'awaiting_feedback', 'completed'],
            'any' => ['pending', 'in_progress', 'testing', 'awaiting_feedback', 'completed', 'delayed'],
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WorkflowTaskComment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkflowTaskActivity::class);
    }

    public function scopeWithSummary($query)
    {
        return $query->withCount([
            'comments as comments_count',
            'activities as activity_count',
        ])->with(['assignee:id,name,email', 'creator:id,name,email']);
    }

    public function restrictableRoles(): array
    {
        return array_filter($this->restricted_roles ?? [], fn ($role) => is_string($role));
    }

    public function allowedTransitions(): array
    {
        return array_merge(self::defaultTransitions(), $this->allowed_transitions ?? []);
    }
}
