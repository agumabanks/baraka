<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityIncident extends Model
{
    protected $fillable = [
        'title',
        'description',
        'severity', // 'low', 'medium', 'high', 'critical'
        'incident_type',
        'status', // 'open', 'investigating', 'contained', 'resolved', 'closed'
        'detected_at',
        'detected_by',
        'assigned_to',
        'assigned_at',
        'resolved_at',
        'resolved_by',
        'affected_systems',
        'incident_data',
        'root_cause',
        'lessons_learned',
    ];

    protected $casts = [
        'affected_systems' => 'array',
        'incident_data' => 'array',
        'detected_at' => 'datetime',
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the user who detected this incident
     */
    public function detector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detected_by');
    }

    /**
     * Get the user assigned to this incident
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who resolved this incident
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get incident timeline/events
     */
    public function timeline(): HasMany
    {
        return $this->hasMany(SecurityIncidentTimeline::class);
    }

    /**
     * Scope for active incidents
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['open', 'investigating', 'contained']);
    }

    /**
     * Scope for incidents by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Check if incident is critical
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Check if incident is resolved
     */
    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Get time to detection
     */
    public function getTimeToDetection(): ?string
    {
        if ($this->detected_at && $this->created_at) {
            $diff = $this->detected_at->diffForHumans($this->created_at, true);
            return $diff;
        }
        return null;
    }

    /**
     * Get time to resolution
     */
    public function getTimeToResolution(): ?string
    {
        if ($this->resolved_at && $this->detected_at) {
            return $this->detected_at->diffForHumans($this->resolved_at, true);
        }
        return null;
    }
}