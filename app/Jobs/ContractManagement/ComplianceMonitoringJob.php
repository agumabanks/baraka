<?php

namespace App\Jobs\ContractManagement;

use App\Services\ContractComplianceService;
use App\Models\Contract;
use App\Models\ContractCompliance;
use App\Events\ContractComplianceBreached;
use App\Events\ContractComplianceEscalated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Compliance Monitoring Job
 * 
 * Specialized job for continuous compliance monitoring including:
 * - Automated compliance checks
 * - Performance threshold monitoring
 * - Breach detection and escalation
 * - Compliance reporting
 * - Risk assessment updates
 */
class ComplianceMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes
    public $tries = 3;
    public $backoff = [120, 300, 600]; // 2 min, 5 min, 10 min

    public function __construct(
        private ?int $contractId = null,
        private ?string $complianceType = null,
        private array $options = []
    ) {}

    public function handle(ContractComplianceService $complianceService): void
    {
        try {
            Log::info('Compliance monitoring job started', [
                'contract_id' => $this->contractId,
                'compliance_type' => $this->complianceType
            ]);

            if ($this->contractId) {
                $this->checkSingleContract($complianceService);
            } else {
                $this->checkAllContracts($complianceService);
            }

            Log::info('Compliance monitoring job completed');

        } catch (\Exception $e) {
            Log::error('Compliance monitoring job failed', [
                'error' => $e->getMessage(),
                'contract_id' => $this->contractId
            ]);
            throw $e;
        }
    }

    private function checkSingleContract(ContractComplianceService $complianceService): void
    {
        $contract = Contract::find($this->contractId);
        
        if (!$contract) {
            Log::warning('Contract not found for compliance check', ['contract_id' => $this->contractId]);
            return;
        }

        $complianceStatus = $complianceService->getContractComplianceStatus($contract->id);
        
        if ($complianceStatus['overall_score'] < 80) {
            $this->handleComplianceIssues($contract, $complianceStatus);
        }
    }

    private function checkAllContracts(ContractComplianceService $complianceService): void
    {
        $query = Contract::where('status', 'active');
        
        if ($this->complianceType) {
            $query->whereHas('compliances', function($q) {
                $q->where('compliance_type', $this->complianceType);
            });
        }

        $contracts = $query->chunk(25, function($contracts) use ($complianceService) {
            foreach ($contracts as $contract) {
                try {
                    $complianceStatus = $complianceService->getContractComplianceStatus($contract->id);
                    
                    if ($complianceStatus['overall_score'] < $this->getAlertThreshold()) {
                        $this->handleComplianceIssues($contract, $complianceStatus);
                    }
                    
                } catch (\Exception $e) {
                    Log::error('Contract compliance check failed', [
                        'contract_id' => $contract->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }

    private function handleComplianceIssues(Contract $contract, array $complianceStatus): void
    {
        foreach ($complianceStatus['requirements'] as $requirement) {
            if ($requirement['status'] === 'breached') {
                $this->processBreach($contract, $requirement);
            }
        }
    }

    private function processBreach(Contract $contract, array $requirement): void
    {
        $compliance = ContractCompliance::where('contract_id', $contract->id)
                                      ->where('requirement_name', $requirement['name'])
                                      ->first();
        
        if ($compliance) {
            // Update breach count
            $compliance->increment('consecutive_breaches');
            
            // Fire breach event
            event(new ContractComplianceBreached($compliance, [
                'performance_percentage' => $requirement['performance'],
                'is_critical' => $requirement['is_critical']
            ]));

            // Escalate if needed
            if ($compliance->consecutive_breaches >= 3) {
                $escalationLevel = $compliance->consecutive_breaches >= 5 ? 2 : 1;
                event(new ContractComplianceEscalated($compliance, $escalationLevel));
            }
        }
    }

    private function getAlertThreshold(): int
    {
        return $this->options['alert_threshold'] ?? 80;
    }
}