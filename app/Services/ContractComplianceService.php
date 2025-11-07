<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractCompliance;
use App\Models\Shipment;
use App\Models\Customer;
use App\Events\ContractComplianceBreached;
use App\Events\ContractComplianceEscalated;
use App\Events\ContractComplianceResolved;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Contract Compliance Service
 * 
 * Handles compliance monitoring and alerting including:
 * - SLA compliance tracking
 * - Performance metrics and reporting
 * - Breach detection and alerting
 * - Compliance score calculation
 * - Audit trail maintenance
 */
class ContractComplianceService
{
    private const DEFAULT_CHECK_FREQUENCIES = [
        'daily' => 1,
        'weekly' => 7,
        'monthly' => 30,
        'quarterly' => 90,
        'annually' => 365
    ];

    private const BREACH_THRESHOLDS = [
        'consecutive_breaches' => 3,
        'performance_drop' => 10, // percentage points
        'critical_threshold' => 80 // minimum acceptable performance
    ];

    public function __construct(
        private WebhookManagementService $webhookService
    ) {}

    /**
     * Initialize compliance monitoring for a contract
     */
    public function initializeContractMonitoring(Contract $contract): array
    {
        $requirements = $contract->compliance_requirements ?? [];
        $createdRequirements = [];

        foreach ($requirements as $requirementName => $config) {
            $compliance = $this->createComplianceRequirement($contract, $requirementName, $config);
            $createdRequirements[] = $compliance;
        }

        // Set up automated monitoring schedule
        $this->setupAutomatedMonitoring($contract, $createdRequirements);

        Log::info('Contract compliance monitoring initialized', [
            'contract_id' => $contract->id,
            'requirements_count' => count($createdRequirements)
        ]);

        return $createdRequirements;
    }

    /**
     * Update compliance performance for a specific requirement
     */
    public function updateCompliancePerformance(
        int $contractId,
        string $requirementName,
        float $actualValue,
        ?Carbon $measuredAt = null
    ): array {
        $measuredAt = $measuredAt ?? now();
        
        return DB::transaction(function() use ($contractId, $requirementName, $actualValue, $measuredAt) {
            $contract = Contract::findOrFail($contractId);
            $compliance = ContractCompliance::where('contract_id', $contractId)
                                          ->where('requirement_name', $requirementName)
                                          ->firstOrFail();

            $updateResult = $compliance->updateCompliance($actualValue, $measuredAt);
            
            // Log the update
            $this->logComplianceUpdate($contract, $compliance, $actualValue, $updateResult);
            
            // Handle breaches and escalations
            if ($updateResult['is_breached']) {
                $this->handleComplianceBreach($contract, $compliance, $updateResult);
            } elseif ($updateResult['status_changed']) {
                $this->handleComplianceImprovement($contract, $compliance, $updateResult);
            }

            return array_merge($updateResult, [
                'compliance_id' => $compliance->id,
                'requirement_name' => $requirementName,
                'updated_at' => now()->toISOString()
            ]);
        });
    }

    /**
     * Get comprehensive compliance status for a contract
     */
    public function getContractComplianceStatus(int $contractId, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        $contract = Contract::findOrFail($contractId);
        
        $complianceScore = ContractCompliance::getContractComplianceScore($contractId, $asOfDate);
        $requirements = ContractCompliance::where('contract_id', $contractId)
                                        ->where('measurement_period_start', '<=', $asOfDate)
                                        ->where('measurement_period_end', '>=', $asOfDate)
                                        ->get()
                                        ->map(function($req) {
                                            return $this->formatComplianceRequirement($req);
                                        })
                                        ->toArray();

        return [
            'contract_id' => $contractId,
            'customer_id' => $contract->customer_id,
            'overall_score' => $complianceScore['score'],
            'status' => $complianceScore['status'],
            'requirements_summary' => [
                'total' => $complianceScore['total_requirements'],
                'compliant' => $complianceScore['compliant_count'],
                'breached' => $complianceScore['breached_count'],
                'warning' => $complianceScore['warning_count'],
                'critical_breaches' => $complianceScore['critical_count']
            ],
            'requirements' => $requirements,
            'last_updated' => now()->toISOString()
        ];
    }

    private function createComplianceRequirement(Contract $contract, string $requirementName, array $config): ContractCompliance
    {
        return ContractCompliance::create([
            'contract_id' => $contract->id,
            'compliance_type' => $config['type'],
            'requirement_name' => str_replace('_', ' ', ucfirst($requirementName)),
            'requirement_description' => $config['description'] ?? "Compliance requirement for {$requirementName}",
            'target_value' => $config['target'],
            'check_frequency' => $config['frequency'] ?? 'monthly',
            'is_critical' => $config['critical'] ?? false,
            'measurement_period_start' => now()->startOfPeriod($config['frequency'] ?? 'monthly'),
            'measurement_period_end' => now()->endOfPeriod($config['frequency'] ?? 'monthly'),
            'next_check_due' => $this->calculateNextCheckDue($config['frequency'] ?? 'monthly'),
            'auto_resolution_enabled' => $config['auto_resolution'] ?? false,
            'alert_threshold' => $config['alert_threshold'] ?? 85
        ]);
    }

    private function calculateNextCheckDue(string $frequency): Carbon
    {
        return match($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addQuarter(),
            'annually' => now()->addYear(),
            default => now()->addWeek()
        };
    }

    private function setupAutomatedMonitoring(Contract $contract, array $requirements): void
    {
        foreach ($requirements as $requirement) {
            $frequency = $requirement->check_frequency;
            $nextCheck = $requirement->next_check_due;
            
            Log::info('Scheduled compliance check', [
                'contract_id' => $contract->id,
                'requirement' => $requirement->requirement_name,
                'frequency' => $frequency,
                'next_check' => $nextCheck->toISOString()
            ]);
        }
    }

    private function handleComplianceBreach(Contract $contract, ContractCompliance $compliance, array $updateResult): void
    {
        event(new ContractComplianceBreached($compliance, $updateResult));

        if ($compliance->consecutive_breaches >= self::BREACH_THRESHOLDS['consecutive_breaches']) {
            event(new ContractComplianceEscalated($compliance, $compliance->escalation_level));
        }
    }

    private function handleComplianceImprovement(Contract $contract, ContractCompliance $compliance, array $updateResult): void
    {
        if ($updateResult['new_status'] === 'compliant') {
            event(new ContractComplianceResolved($compliance));
        }
    }

    private function formatComplianceRequirement(ContractCompliance $requirement): array
    {
        return [
            'id' => $requirement->id,
            'name' => $requirement->requirement_name,
            'type' => $requirement->compliance_type,
            'status' => $requirement->compliance_status,
            'target' => $requirement->target_value,
            'actual' => $requirement->actual_value,
            'performance' => $requirement->performance_percentage,
            'is_critical' => $requirement->is_critical,
            'last_checked' => $requirement->last_checked_at?->toISOString(),
            'next_check' => $requirement->next_check_due?->toISOString(),
            'consecutive_breaches' => $requirement->consecutive_breaches,
            'risk_level' => $requirement->getRiskLevel()
        ];
    }

    private function logComplianceUpdate(Contract $contract, ContractCompliance $compliance, float $actualValue, array $updateResult): void
    {
        Log::info('Compliance performance updated', [
            'contract_id' => $contract->id,
            'requirement_name' => $compliance->requirement_name,
            'actual_value' => $actualValue,
            'performance_percentage' => $compliance->performance_percentage,
            'status' => $compliance->compliance_status,
            'status_changed' => $updateResult['status_changed'],
            'is_breached' => $updateResult['is_breached']
        ]);
    }
}