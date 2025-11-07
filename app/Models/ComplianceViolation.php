<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'violation_id',
        'compliance_framework',
        'violation_type',
        'severity',
        'description',
        'affected_records',
        'discovered_by',
        'discovered_by_user_id',
        'discovered_at',
        'resolved_by_user_id',
        'resolved_at',
        'resolution_notes',
        'remediation_steps',
        'is_false_positive',
    ];

    protected $casts = [
        'affected_records' => 'array',
        'remediation_steps' => 'array',
        'discovered_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_false_positive' => 'boolean',
    ];

    /**
     * Get the user who discovered the violation
     */
    public function discoveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discovered_by_user_id');
    }

    /**
     * Get the user who resolved the violation
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /**
     * Get violation status
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_false_positive) {
            return 'false_positive';
        }
        
        if ($this->resolved_at) {
            return 'resolved';
        }
        
        return 'open';
    }

    /**
     * Get severity color for UI
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get days since discovery
     */
    public function getDaysOpenAttribute(): int
    {
        return $this->discovered_at->diffInDays(now());
    }

    /**
     * Scope for unresolved violations
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at')
                    ->where('is_false_positive', false);
    }

    /**
     * Scope for critical violations
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope for framework filtering
     */
    public function scopeForFramework($query, $framework)
    {
        return $query->where('compliance_framework', $framework);
    }
}