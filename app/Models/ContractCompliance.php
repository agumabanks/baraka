<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class ContractCompliance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'contract_id',
        'compliance_type',
        'requirement_name',
        'requirement_description',
        'compliance_status',
        'measurement_period_start',
        'measurement_period_end',
        'target_value',
        'actual_value',
        'performance_percentage',
        'is_critical',
        'last_checked_at',
        'next_check_due',
        'breach_count',
        'consecutive_breaches',
        'resolution_deadline',
        'resolution_actions',
        'responsible_party',
        'escalation_level',
        'penalty_amount',
        'penalty_applied',
        'penalty_date',
        'remediation_notes',
        'proof_documents',
        'check_frequency',
        'auto_resolution_enabled',
        'alert_threshold',
        'created_at_check',
        'metadata'
    ];

    protected $casts = [
        'measurement_period_start' => 'date',
        'measurement_period_end' => 'date',
        'target_value' => 'decimal:4',
        'actual_value' => 'decimal:4',
        'performance_percentage' => 'decimal:2',
        'is_critical' => 'boolean',
        'last_checked_at' => 'datetime',
        'next_check_due' => 'datetime',
        'breach_count' => 'integer',
        'consecutive_breaches' => 'integer',
        'resolution_deadline' => 'datetime',
        'resolution_actions' => 'array',
        'penalty_amount' => 'decimal:2',
        'penalty_applied' => 'boolean',
        'penalty_date' => 'datetime',
        'remediation_notes' => 'array',
        'proof_documents' => 'array',
        'check_frequency' => 'string',
        'auto_resolution_enabled' => 'boolean',
        'alert_threshold' => 'decimal:2',
        'created_at_check' => 'datetime',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ContractCompliance')
            ->logOnly(['compliance_type', 'requirement_name', 'compliance_status', 'performance_percentage'])
            ->setDescriptionForEvent(fn (string $eventName) => "Contract compliance {$eventName}");
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('compliance_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('compliance_status', $status);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeBreached($query)
    {
        return $query->where('compliance_status', 'breached');
    }

    public function scopeCompliant($query)
    {
        return $query->where('compliance_status', 'compliant');
    }

    public function scopeOverdue($query)
    {
        return $query->where('next_check_due', '<', now());
    }

    public function scopeEscalated($query)
    {
        return $query->where('escalation_level', '>', 0);
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('check_frequency', $frequency);
    }

    public function scopeByPeriod($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('measurement_period_start', [$startDate, $endDate])
                    ->orWhereBetween('measurement_period_end', [$startDate, $endDate]);
    }

    public function scopeRequiringAttention($query)
    {
        return $query->where(function($q) {
            $q->where('compliance_status', 'breached')
              ->orWhere('next_check_due', '<', now())
              ->orWhere('consecutive_breaches', '>=', 3);
        });
    }

    // Business Logic
    public function updateCompliance(float $actualValue, ?Carbon $checkedAt = null): array
    {
        $checkedAt = $checkedAt ?? now();
        $wasBreached = $this->compliance_status === 'breached';
        $isBreached = $this->isBreach($actualValue);
        
        $this->update([
            'actual_value' => $actualValue,
            'performance_percentage' => $this->calculatePerformancePercentage($actualValue),
            'last_checked_at' => $checkedAt,
            'measurement_period_end' => $this->updateMeasurementPeriod($checkedAt),
            'next_check_due' => $this->calculateNextCheckDue()
        ]);

        if ($isBreached) {
            $this->handleBreach($wasBreached);
        } else {
            $this->handleCompliance();
        }

        $this->checkForEscalation();

        return [
            'status_changed' => $wasBreached !== $isBreached,
            'is_breached' => $isBreached,
            'new_status' => $this->compliance_status,
            'performance_percentage' => $this->performance_percentage,
            'escalation_level' => $this->escalation_level
        ];
    }

    public function isBreach(float $value): bool
    {
        return match($this->compliance_type) {
            'minimum' => $value < $this->target_value,
            'maximum' => $value > $this->target_value,
            'exact' => $value != $this->target_value,
            'range' => $this->isValueOutOfRange($value),
            default => false
        };
    }

    public function calculatePerformancePercentage(float $actualValue): float
    {
        if ($this->target_value == 0) {
            return 100.0;
        }

        return match($this->compliance_type) {
            'minimum' => min(100, ($actualValue / $this->target_value) * 100),
            'maximum' => min(100, ($this->target_value / $actualValue) * 100),
            'exact' => $actualValue == $this->target_value ? 100 : 0,
            'range' => $this->isValueInRange($actualValue) ? 100 : 0,
            default => 0
        };
    }

    public function getComplianceScore(): float
    {
        return $this->performance_percentage ?? 0;
    }

    public function getRiskLevel(): string
    {
        if ($this->is_critical && $this->compliance_status === 'breached') {
            return 'critical';
        }

        if ($this->consecutive_breaches >= 3) {
            return 'high';
        }

        if ($this->consecutive_breaches >= 2) {
            return 'medium';
        }

        if ($this->compliance_status === 'warning' || $this->escalation_level > 0) {
            return 'medium';
        }

        if ($this->performance_percentage < 80) {
            return 'low';
        }

        return 'minimal';
    }

    public function getRequiredActions(): array
    {
        $actions = [];

        if ($this->compliance_status === 'breached') {
            $actions[] = [
                'type' => 'immediate_action',
                'description' => 'Address compliance breach immediately',
                'deadline' => $this->resolution_deadline,
                'priority' => 'high'
            ];
        }

        if ($this->next_check_due->isPast()) {
            $actions[] = [
                'type' => 'overdue_check',
                'description' => 'Perform overdue compliance check',
                'deadline' => now(),
                'priority' => 'medium'
            ];
        }

        if ($this->consecutive_breaches >= 2) {
            $actions[] = [
                'type' => 'escalation',
                'description' => 'Escalate to management',
                'deadline' => now()->addDays(1),
                'priority' => 'high'
            ];
        }

        if ($this->auto_resolution_enabled && $this->canAutoResolve()) {
            $actions[] = [
                'type' => 'auto_resolution',
                'description' => 'Apply automatic resolution',
                'deadline' => now(),
                'priority' => 'medium'
            ];
        }

        return $actions;
    }

    public function applyPenalty(float $penaltyAmount, ?Carbon $penaltyDate = null): bool
    {
        $penaltyDate = $penaltyDate ?? now();
        
        return $this->update([
            'penalty_amount' => $penaltyAmount,
            'penalty_applied' => true,
            'penalty_date' => $penaltyDate,
            'escalation_level' => max($this->escalation_level, 2)
        ]);
    }

    public function resolveBreach(array $resolutionActions, ?string $notes = null): bool
    {
        $this->update([
            'compliance_status' => 'compliant',
            'resolution_actions' => $resolutionActions,
            'consecutive_breaches' => 0,
            'escalation_level' => 0,
            'remediation_notes' => array_merge(
                $this->remediation_notes ?? [],
                ['resolution_notes' => $notes, 'resolved_at' => now()->toISOString()]
            )
        ]);

        return true;
    }

    public function createProofDocument(string $documentName, string $documentPath, string $documentType): bool
    {
        $documents = $this->proof_documents ?? [];
        $documents[] = [
            'name' => $documentName,
            'path' => $documentPath,
            'type' => $documentType,
            'uploaded_at' => now()->toISOString(),
            'uploaded_by' => auth()->id()
        ];

        return $this->update(['proof_documents' => $documents]);
    }

    private function handleBreach(bool $wasBreached): void
    {
        $updateData = [
            'compliance_status' => 'breached',
            'breach_count' => $this->breach_count + 1
        ];

        if (!$wasBreached) {
            // New breach
            $updateData['consecutive_breaches'] = $this->consecutive_breaches + 1;
            $updateData['resolution_deadline'] = now()->addDays(7); // 7 days to resolve
            
            // Create immediate notification
            event(new \App\Events\ContractComplianceBreached($this));
        } else {
            // Continuing breach
            $updateData['consecutive_breaches'] = $this->consecutive_breaches + 1;
        }

        $this->update($updateData);
    }

    private function handleCompliance(): void
    {
        $this->update([
            'compliance_status' => $this->performance_percentage >= 90 ? 'compliant' : 'warning',
            'consecutive_breaches' => 0,
            'resolution_deadline' => null
        ]);
    }

    private function checkForEscalation(): void
    {
        $newEscalationLevel = match(true) {
            $this->consecutive_breaches >= 5 => 4, // Executive escalation
            $this->consecutive_breaches >= 3 => 3, // Management escalation
            $this->consecutive_breaches >= 2 => 2, // Department head
            $this->is_critical && $this->compliance_status === 'breached' => 2, // Critical breach
            default => 0
        };

        if ($newEscalationLevel > $this->escalation_level) {
            $this->update(['escalation_level' => $newEscalationLevel]);
            
            // Trigger escalation event
            event(new \App\Events\ContractComplianceEscalated($this, $newEscalationLevel));
        }
    }

    private function isValueOutOfRange(float $value): bool
    {
        $range = $this->target_value; // This would be stored as a range array
        return $value < ($range['min'] ?? 0) || $value > ($range['max'] ?? PHP_FLOAT_MAX);
    }

    private function isValueInRange(float $value): bool
    {
        $range = $this->target_value;
        return $value >= ($range['min'] ?? 0) && $value <= ($range['max'] ?? PHP_FLOAT_MAX);
    }

    private function updateMeasurementPeriod(Carbon $checkedAt): Carbon
    {
        return match($this->check_frequency) {
            'daily' => $checkedAt,
            'weekly' => $checkedAt->copy()->endOfWeek(),
            'monthly' => $checkedAt->copy()->endOfMonth(),
            'quarterly' => $checkedAt->copy()->endOfQuarter(),
            'annually' => $checkedAt->copy()->endOfYear(),
            default => $checkedAt
        };
    }

    private function calculateNextCheckDue(): Carbon
    {
        return match($this->check_frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addQuarter(),
            'annually' => now()->addYear(),
            default => now()->addWeek()
        };
    }

    private function canAutoResolve(): bool
    {
        // Simple auto-resolution logic
        return $this->consecutive_breaches <= 1 && 
               $this->performance_percentage >= 95 && 
               !empty($this->resolution_actions);
    }

    public static function getContractComplianceScore(int $contractId, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        
        $requirements = self::where('contract_id', $contractId)
                          ->where('measurement_period_start', '<=', $asOfDate)
                          ->where('measurement_period_end', '>=', $asOfDate)
                          ->get();

        if ($requirements->isEmpty()) {
            return [
                'score' => 100,
                'status' => 'no_requirements',
                'total_requirements' => 0,
                'compliant_count' => 0,
                'breached_count' => 0,
                'warning_count' => 0
            ];
        }

        $totalRequirements = $requirements->count();
        $compliantCount = $requirements->where('compliance_status', 'compliant')->count();
        $breachedCount = $requirements->where('compliance_status', 'breached')->count();
        $warningCount = $requirements->where('compliance_status', 'warning')->count();

        $score = ($compliantCount / $totalRequirements) * 100;
        
        $status = match(true) {
            $breachedCount > 0 => 'breached',
            $warningCount > 0 => 'warning',
            $score >= 95 => 'excellent',
            $score >= 80 => 'good',
            default => 'needs_attention'
        };

        return [
            'score' => round($score, 2),
            'status' => $status,
            'total_requirements' => $totalRequirements,
            'compliant_count' => $compliantCount,
            'breached_count' => $breachedCount,
            'warning_count' => $warningCount,
            'critical_count' => $requirements->where('is_critical', true)->where('compliance_status', 'breached')->count()
        ];
    }

    public static function getExpiringDeadlines(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('resolution_deadline', '<=', now()->addDays($days))
                  ->where('resolution_deadline', '>=', now())
                  ->where('compliance_status', 'breached')
                  ->with(['contract.customer'])
                  ->orderBy('resolution_deadline', 'asc')
                  ->get();
    }
}