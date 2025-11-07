<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessibilityComplianceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'page_url',
        'test_type',
        'wcag_version',
        'test_results',
        'compliance_score',
        'violations',
        'warnings',
        'passes',
        'tested_by',
        'tested_at',
        'metadata',
    ];

    protected $casts = [
        'wcag_version' => 'array',
        'test_results' => 'array',
        'violations' => 'array',
        'warnings' => 'array',
        'passes' => 'array',
        'metadata' => 'array',
        'tested_at' => 'datetime',
    ];

    /**
     * Get the user who conducted the test
     */
    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }

    /**
     * Get compliance status based on score
     */
    public function getComplianceStatusAttribute(): string
    {
        return match (true) {
            $this->compliance_score >= 95 => 'excellent',
            $this->compliance_score >= 85 => 'good',
            $this->compliance_score >= 70 => 'acceptable',
            $this->compliance_score >= 50 => 'poor',
            default => 'critical',
        };
    }

    /**
     * Get WCAG level from version array
     */
    public function getWcagLevelAttribute(): string
    {
        $versions = $this->wcag_version ?? [];
        if (is_array($versions)) {
            return implode(' ', $versions);
        }
        return (string) $versions;
    }

    /**
     * Get critical violations count
     */
    public function getCriticalViolationsCountAttribute(): int
    {
        return collect($this->violations ?? [])->filter(function ($violation) {
            return $violation['severity'] ?? '' === 'critical';
        })->count();
    }

    /**
     * Get total issues count
     */
    public function getTotalIssuesAttribute(): int
    {
        return count($this->violations ?? []) + count($this->warnings ?? []);
    }

    /**
     * Scope for compliance score filtering
     */
    public function scopeComplianceScore($query, $minScore = 70)
    {
        return $query->where('compliance_score', '>=', $minScore);
    }

    /**
     * Scope for recent tests
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('tested_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for failed tests
     */
    public function scopeFailed($query)
    {
        return $query->where('compliance_score', '<', 70);
    }
}