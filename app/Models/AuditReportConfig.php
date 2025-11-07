<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditReportConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_name',
        'report_type',
        'included_modules',
        'filters',
        'recipients',
        'is_active',
        'last_generated_at',
        'format',
        'custom_config',
    ];

    protected $casts = [
        'included_modules' => 'array',
        'filters' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
        'custom_config' => 'array',
    ];

    /**
     * Get report type display name
     */
    public function getReportTypeDisplayAttribute(): string
    {
        return match ($this->report_type) {
            'daily' => 'Daily Report',
            'weekly' => 'Weekly Report',
            'monthly' => 'Monthly Report',
            'on_demand' => 'On-Demand Report',
            default => ucfirst($this->report_type),
        };
    }

    /**
     * Check if report is due for generation
     */
    public function getIsDueForGenerationAttribute(): bool
    {
        if (!$this->is_active || !$this->last_generated_at) {
            return true;
        }

        return match ($this->report_type) {
            'daily' => $this->last_generated_at->addDay()->isPast(),
            'weekly' => $this->last_generated_at->addWeek()->isPast(),
            'monthly' => $this->last_generated_at->addMonth()->isPast(),
            default => false,
        };
    }
}