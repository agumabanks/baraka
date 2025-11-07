<?php

namespace App\Services;

use App\Events\ContractActivated;
use App\Events\ContractVolumeCommitmentReached;
use App\Events\ContractMilestoneAchieved;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Contract Management Service - Main service for contract operations
 * 
 * This service orchestrates all contract-related operations and integrates
 * with other contract management services for a complete solution.
 */
class ContractManagementService
{
    public function __construct(
        private VolumeDiscountService $volumeService,
        private ContractNotificationService $notificationService
    ) {}

    /**
     * Create a contract from template with customer data
     */
    public function createContractFromTemplate(
        int $templateId,
        array $customerData,
        array $contractData
    ): Contract {
        return DB::transaction(function() use ($templateId, $customerData, $contractData) {
            // This would be implemented with the actual service logic
            // For now, returning a placeholder implementation
            Log::info('Contract created from template', [
                'template_id' => $templateId,
                'customer_data' => $customerData
            ]);
            
            return new Contract($contractData);
        });
    }

    /**
     * Create a new contract
     */
    public function createContract(array $contractData): Contract
    {
        $contract = Contract::create($contractData);
        
        Log::info('Contract created', ['contract_id' => $contract->id]);
        
        return $contract;
    }

    /**
     * Update a contract
     */
    public function updateContract(int $contractId, array $updateData): Contract
    {
        $contract = Contract::findOrFail($contractId);
        $contract->update($updateData);
        
        Log::info('Contract updated', ['contract_id' => $contractId]);
        
        return $contract->fresh();
    }

    /**
     * Activate a contract
     */
    public function activateContract(int $contractId, ?int $activatedBy = null): array
    {
        $contract = Contract::findOrFail($contractId);
        
        $contract->update([
            'status' => 'active',
            'activated_at' => now(),
            'activated_by' => $activatedBy
        ]);

        // Fire event
        event(new ContractActivated($contract, $activatedBy));

        // Clear cache
        Cache::tags(['contracts'])->flush();

        Log::info('Contract activated', ['contract_id' => $contractId]);

        return [
            'success' => true,
            'contract' => $contract->fresh(),
            'activated_by' => $activatedBy,
            'activated_at' => now()
        ];
    }

    /**
     * Suspend a contract
     */
    public function suspendContract(
        int $contractId,
        string $reason,
        ?int $suspensionDuration = null,
        ?int $suspendedBy = null
    ): array {
        $contract = Contract::findOrFail($contractId);
        
        $suspensionData = [
            'status' => 'suspended',
            'suspension_reason' => $reason,
            'suspended_at' => now(),
            'suspended_by' => $suspendedBy
        ];
        
        if ($suspensionDuration) {
            $suspensionData['suspension_ends_at'] = now()->addDays($suspensionDuration);
        }

        $contract->update($suspensionData);

        Log::info('Contract suspended', [
            'contract_id' => $contractId,
            'reason' => $reason,
            'duration' => $suspensionDuration
        ]);

        return [
            'success' => true,
            'contract' => $contract->fresh(),
            'suspension_details' => $suspensionData
        ];
    }

    /**
     * Process contract renewal
     */
    public function processContractRenewal(
        int $contractId,
        Carbon $newEndDate,
        array $renewalDetails = [],
        ?int $renewedBy = null
    ): array {
        $contract = Contract::findOrFail($contractId);
        
        // Update contract with renewal data
        $contract->update([
            'end_date' => $newEndDate,
            'status' => 'active',
            'renewal_details' => array_merge($renewalDetails, [
                'renewed_by' => $renewedBy,
                'renewed_at' => now(),
                'renewal_count' => ($contract->renewal_count ?? 0) + 1
            ])
        ]);

        Log::info('Contract renewed', [
            'contract_id' => $contractId,
            'new_end_date' => $newEndDate->format('Y-m-d')
        ]);

        return [
            'success' => true,
            'action_taken' => 'renewed',
            'new_end_date' => $newEndDate->format('Y-m-d'),
            'contract' => $contract->fresh()
        ];
    }

    /**
     * Check contract compliance status
     */
    public function checkComplianceStatus(int $contractId): array
    {
        return app(ContractComplianceService::class)->getContractComplianceStatus($contractId);
    }

    /**
     * Track milestone progress
     */
    public function trackMilestoneProgress(int $customerId): array
    {
        // Implementation for milestone tracking
        return [
            'milestones' => [],
            'next_milestone' => null
        ];
    }

    /**
     * Apply contract pricing to shipment
     */
    public function applyContractPricing(int $contractId, array $shipmentData): array
    {
        $contract = Contract::findOrFail($contractId);
        $volumeService = $this->volumeService;
        
        // Calculate volume and discounts
        $volume = $shipmentData['volume'] ?? 1;
        $discounts = $volumeService->calculateDiscountsForVolume($contract, $volume);
        
        // Get base pricing from dynamic pricing service
        $basePrice = app(DynamicPricingService::class)
            ->calculateInstantQuote(
                $shipmentData['origin'],
                $shipmentData['destination'],
                $shipmentData,
                $shipmentData['service_level'] ?? 'standard',
                $contract->customer_id
            );

        // Apply contract discounts
        if ($discounts['applicable']) {
            $basePrice['final_total'] *= (1 - $discounts['discount_percentage'] / 100);
        }

        return [
            'base_pricing' => $basePrice,
            'contract_discounts' => $discounts,
            'final_total' => $basePrice['final_total'],
            'savings_amount' => $discounts['applicable'] ? 
                ($basePrice['base_amount'] * $discounts['discount_percentage'] / 100) : 0
        ];
    }

    /**
     * Send renewal alerts
     */
    public function notifyRenewalAlerts(int $daysBefore = 30): void
    {
        $contracts = Contract::where('status', 'active')
                           ->where('end_date', '<=', now()->addDays($daysBefore))
                           ->get();

        foreach ($contracts as $contract) {
            $this->notificationService->sendContractRenewalNotifications($contract);
        }
    }

    /**
     * Get system statistics
     */
    public function getSystemStatistics(): array
    {
        return Cache::remember('contract_system_stats', 3600, function() {
            return [
                'total_contracts' => Contract::count(),
                'active_contracts' => Contract::where('status', 'active')->count(),
                'expiring_contracts' => Contract::where('status', 'active')
                                               ->where('end_date', '<=', now()->addDays(30))
                                               ->count(),
                'expired_contracts' => Contract::where('status', 'expired')->count(),
                'total_volume' => Contract::where('status', 'active')->sum('current_volume'),
                'compliance_breaches' => \App\Models\ContractCompliance::where('compliance_status', 'breached')->count()
            ];
        });
    }

    /**
     * Process batch operations
     */
    public function performBatchOperation(string $operation, array $parameters = []): array
    {
        $contractIds = $parameters['contract_ids'] ?? [];
        $results = [];
        
        foreach ($contractIds as $contractId) {
            try {
                $result = match($operation) {
                    'activate' => $this->activateContract($contractId),
                    'compliance_check' => $this->checkComplianceStatus($contractId),
                    'volume_sync' => $this->volumeService->updateContractVolume($contractId, 0),
                    default => null
                };
                
                $results[] = [
                    'contract_id' => $contractId,
                    'success' => true,
                    'result' => $result
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'contract_id' => $contractId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'operation' => $operation,
            'contracts_processed' => count($contractIds),
            'results' => $results
        ];
    }

    /**
     * Generate contract report
     */
    public function generateContractReport(int $contractId): array
    {
        $contract = Contract::with([
            'customer',
            'template',
            'volumeDiscounts',
            'compliances',
            'notifications'
        ])->findOrFail($contractId);

        $complianceStatus = $this->checkComplianceStatus($contractId);
        $volumeInfo = $this->volumeService->getTierProgressionInfo($contractId);

        return [
            'contract' => $contract->getContractSummary(),
            'compliance' => $complianceStatus,
            'volume' => $volumeInfo,
            'financial' => [
                'estimated_monthly_value' => $this->calculateMonthlyValue($contract),
                'potential_savings' => $this->calculatePotentialSavings($contract),
                'total_discounts' => $this->calculateTotalDiscounts($contract)
            ]
        ];
    }

    private function calculateMonthlyValue(Contract $contract): float
    {
        $volume = $contract->current_volume ?? 0;
        $averageRate = 5.0; // Placeholder
        return ($volume / 12) * $averageRate;
    }

    private function calculatePotentialSavings(Contract $contract): float
    {
        $applicableTier = $contract->volumeDiscounts()
                                 ->where('volume_requirement', '<=', $contract->current_volume ?? 0)
                                 ->orderBy('volume_requirement', 'desc')
                                 ->first();

        if (!$applicableTier) {
            return 0;
        }

        $monthlyValue = $this->calculateMonthlyValue($contract);
        return $monthlyValue * ($applicableTier->discount_percentage / 100);
    }

    private function calculateTotalDiscounts(Contract $contract): float
    {
        $totalDiscounts = 0;
        foreach ($contract->volumeDiscounts as $tier) {
            $monthlyValue = $this->calculateMonthlyValue($contract);
            $totalDiscounts += $monthlyValue * ($tier->discount_percentage / 100);
        }
        return $totalDiscounts;
    }
}