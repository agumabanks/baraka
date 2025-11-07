<?php

namespace App\Jobs\ContractManagement;

use App\Services\ContractManagementService;
use App\Services\ContractComplianceService;
use App\Services\VolumeDiscountService;
use App\Services\ContractNotificationService;
use App\Models\Contract;
use App\Models\ContractCompliance;
use App\Events\ContractExpiring;
use App\Events\ContractExpired;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Contract Processing Job
 * 
 * Handles automated processing of contract operations including:
 * - Contract expiry processing
 * - Auto-renewal handling
 * - Compliance monitoring
 * - Volume progression tracking
 * - Milestone achievements
 * - Notification triggers
 */
class ContractProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DEFAULT_RETRY_ATTEMPTS = 3;
    private const DEFAULT_TIMEOUT = 3600; // 1 hour
    private const BATCH_SIZE = 50; // Process up to 50 contracts at a time

    public $timeout = self::DEFAULT_TIMEOUT;
    public $tries = self::DEFAULT_RETRY_ATTEMPTS;
    public $backoff = [60, 300, 900]; // Retry after 1 min, 5 min, 15 min

    public function __construct(
        private ?string $operationType = null,
        private ?array $contractIds = null,
        private array $options = []
    ) {}

    /**
     * Execute the job
     */
    public function handle(
        ContractManagementService $contractService,
        ContractComplianceService $complianceService,
        VolumeDiscountService $volumeService,
        ContractNotificationService $notificationService
    ): void {
        try {
            Log::info('Contract processing job started', [
                'operation_type' => $this->operationType,
                'contract_count' => $this->contractIds ? count($this->contractIds) : 'all',
                'options' => $this->options
            ]);

            $result = match($this->operationType) {
                'expiry_processing' => $this->processContractExpiries($contractService, $notificationService),
                'auto_renewal' => $this->processAutoRenewals($contractService, $notificationService),
                'compliance_monitoring' => $this->processComplianceMonitoring($complianceService),
                'volume_progression' => $this->processVolumeProgression($volumeService),
                'milestone_processing' => $this->processMilestoneAchievements($volumeService),
                'batch_notification' => $this->processBatchNotifications($notificationService),
                'system_maintenance' => $this->processSystemMaintenance($contractService, $complianceService, $volumeService),
                default => $this->processFullCycle($contractService, $complianceService, $volumeService, $notificationService)
            };

            Log::info('Contract processing job completed', [
                'operation_type' => $this->operationType,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Contract processing job failed', [
                'operation_type' => $this->operationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process contract expiry notifications and handling
     */
    private function processContractExpiries(
        ContractManagementService $contractService,
        ContractNotificationService $notificationService
    ): array {
        $results = [
            'contracts_checked' => 0,
            'expiring_contracts' => 0,
            'expired_contracts' => 0,
            'notifications_sent' => 0
        ];

        // Get contracts that are expiring in the next 30 days
        $expiringContracts = Contract::where('status', 'active')
                                   ->where('end_date', '<=', now()->addDays(30))
                                   ->where('end_date', '>=', now())
                                   ->chunk(self::BATCH_SIZE, function($contracts) use ($notificationService, &$results) {
                                       foreach ($contracts as $contract) {
                                           $results['contracts_checked']++;
                                           $daysUntilExpiry = $contract->getDaysUntilExpiry();
                                           
                                           // Send renewal notifications
                                           if (in_array($daysUntilExpiry, [30, 15, 7, 3, 1])) {
                                               $notificationService->sendContractRenewalNotifications($contract);
                                               $results['notifications_sent']++;
                                           }
                                           
                                           // Fire expiring event
                                           if ($daysUntilExpiry <= 7) {
                                               event(new ContractExpiring($contract, $daysUntilExpiry));
                                           }
                                       }
                                   });

        // Process expired contracts
        $expiredContracts = Contract::where('status', 'active')
                                  ->where('end_date', '<', now())
                                  ->chunk(self::BATCH_SIZE, function($contracts) use (&$results) {
                                      foreach ($contracts as $contract) {
                                          $results['expired_contracts']++;
                                          $contract->update(['status' => 'expired']);
                                          event(new ContractExpired($contract));
                                      }
                                  });

        $results['expiring_contracts'] = Contract::where('status', 'active')
                                               ->whereBetween('end_date', [now(), now()->addDays(30)])
                                               ->count();

        return $results;
    }

    /**
     * Process auto-renewal for eligible contracts
     */
    private function processAutoRenewals(
        ContractManagementService $contractService,
        ContractNotificationService $notificationService
    ): array {
        $results = [
            'contracts_checked' => 0,
            'auto_renewal_processed' => 0,
            'renewal_failed' => 0,
            'new_contracts_created' => 0
        ];

        $renewableContracts = Contract::where('status', 'active')
                                    ->whereHas('auto_renewal_terms', function($q) {
                                        $q->where('auto_renewal', true);
                                    })
                                    ->where('end_date', '<=', now()->addDays(30))
                                    ->whereDoesntHave('renewals', function($q) {
                                        $q->where('status', 'active');
                                    })
                                    ->chunk(self::BATCH_SIZE, function($contracts) use ($contractService, &$results) {
                                        foreach ($contracts as $contract) {
                                            try {
                                                $results['contracts_checked']++;
                                                
                                                $renewalContract = $contract->createRenewal();
                                                if ($renewalContract) {
                                                    $results['new_contracts_created']++;
                                                    
                                                    $contractService->activateContract($renewalContract->id);
                                                    $results['auto_renewal_processed']++;
                                                    
                                                    // Send renewal confirmation
                                                    $notificationService->sendContractActivationNotifications($renewalContract);
                                                }
                                                
                                            } catch (\Exception $e) {
                                                Log::error('Auto-renewal failed', [
                                                    'contract_id' => $contract->id,
                                                    'error' => $e->getMessage()
                                                ]);
                                                $results['renewal_failed']++;
                                            }
                                        }
                                    });

        return $results;
    }

    /**
     * Process compliance monitoring and checks
     */
    private function processComplianceMonitoring(ContractComplianceService $complianceService): array
    {
        return $complianceService->runAutomatedComplianceChecks();
    }

    /**
     * Process volume progression and tier achievements
     */
    private function processVolumeProgression(VolumeDiscountService $volumeService): array
    {
        $results = [
            'contracts_checked' => 0,
            'tier_achievements' => 0,
            'milestone_updates' => 0
        ];

        $activeContracts = Contract::where('status', 'active')
                                 ->where('current_volume', '>', 0)
                                 ->chunk(self::BATCH_SIZE, function($contracts) use ($volumeService, &$results) {
                                     foreach ($contracts as $contract) {
                                         try {
                                             $results['contracts_checked']++;
                                             
                                             // Check tier progression
                                             $progressionInfo = $volumeService->getTierProgressionInfo($contract->id);
                                             
                                             if (!empty($progressionInfo['tier_achievements'])) {
                                                 $results['tier_achievements'] += count($progressionInfo['tier_achievements']);
                                             }
                                             
                                             // Update milestone progress
                                             $volumeService->checkVolumeMilestones($contract, $contract->current_volume);
                                             $results['milestone_updates']++;
                                             
                                         } catch (\Exception $e) {
                                             Log::error('Volume progression processing failed', [
                                                 'contract_id' => $contract->id,
                                                 'error' => $e->getMessage()
                                             ]);
                                         }
                                     }
                                 });

        return $results;
    }

    /**
     * Process milestone achievements
     */
    private function processMilestoneAchievements(VolumeDiscountService $volumeService): array
    {
        $results = [
            'customers_checked' => 0,
            'milestones_achieved' => 0,
            'notifications_sent' => 0
        ];

        $activeCustomers = \App\Models\Customer::whereHas('contracts', function($q) {
            $q->where('status', 'active');
        })->chunk(self::BATCH_SIZE, function($customers) use ($volumeService, &$results) {
            foreach ($customers as $customer) {
                try {
                    $results['customers_checked']++;
                    
                    $totalVolume = $customer->contracts()
                                           ->where('status', 'active')
                                           ->sum('current_volume');
                    
                    $milestoneCheck = \App\Models\CustomerMilestone::checkAndCreateMilestone(
                        $customer,
                        'shipment_count',
                        $totalVolume
                    );
                    
                    if ($milestoneCheck) {
                        $results['milestones_achieved']++;
                    }
                    
                } catch (\Exception $e) {
                    Log::error('Milestone processing failed', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        return $results;
    }

    /**
     * Process batch notifications
     */
    private function processBatchNotifications(ContractNotificationService $notificationService): array
    {
        $results = [
            'renewal_alerts' => [],
            'compliance_alerts' => []
        ];

        // Send batch renewal alerts
        $results['renewal_alerts'] = $notificationService->sendBatchRenewalAlerts(30);
        
        // Send compliance monitoring alerts
        $results['compliance_alerts'] = $notificationService->sendComplianceMonitoringAlerts();

        return $results;
    }

    /**
     * Process system maintenance tasks
     */
    private function processSystemMaintenance(
        ContractManagementService $contractService,
        ContractComplianceService $complianceService,
        VolumeDiscountService $volumeService
    ): array {
        $results = [
            'cache_cleared' => false,
            'stats_updated' => false,
            'cleanup_performed' => false
        ];

        // Clear expired caches
        \Illuminate\Support\Facades\Cache::tags(['contracts', 'compliance', 'volume'])->flush();
        $results['cache_cleared'] = true;

        // Update system statistics
        $this->updateSystemStatistics();
        $results['stats_updated'] = true;

        // Perform data cleanup
        $this->performDataCleanup();
        $results['cleanup_performed'] = true;

        return $results;
    }

    /**
     * Process full contract management cycle
     */
    private function processFullCycle(
        ContractManagementService $contractService,
        ContractComplianceService $complianceService,
        VolumeDiscountService $volumeService,
        ContractNotificationService $notificationService
    ): array {
        $results = [];

        // Run all processing types
        $results['expiry_processing'] = $this->processContractExpiries($contractService, $notificationService);
        $results['auto_renewal'] = $this->processAutoRenewals($contractService, $notificationService);
        $results['compliance_monitoring'] = $this->processComplianceMonitoring($complianceService);
        $results['volume_progression'] = $this->processVolumeProgression($volumeService);
        $results['milestone_processing'] = $this->processMilestoneAchievements($volumeService);
        $results['batch_notifications'] = $this->processBatchNotifications($notificationService);

        return $results;
    }

    /**
     * Update system statistics and metrics
     */
    private function updateSystemStatistics(): void
    {
        // Update contract statistics
        $stats = [
            'total_contracts' => Contract::count(),
            'active_contracts' => Contract::where('status', 'active')->count(),
            'expiring_contracts' => Contract::where('status', 'active')
                                            ->where('end_date', '<=', now()->addDays(30))
                                            ->count(),
            'expired_contracts' => Contract::where('status', 'expired')->count(),
            'compliance_breaches' => ContractCompliance::where('compliance_status', 'breached')->count(),
            'total_volume' => Contract::where('status', 'active')->sum('current_volume')
        ];

        \Illuminate\Support\Facades\Cache::put('contract_system_stats', $stats, now()->addHours(1));
    }

    /**
     * Perform data cleanup tasks
     */
    private function performDataCleanup(): void
    {
        // Clean up old contract notifications (older than 1 year)
        \App\Models\ContractNotification::where('scheduled_at', '<', now()->subYear())
                                      ->delete();

        // Archive old contract audit logs (older than 2 years)
        \App\Models\ContractAuditLog::where('created_at', '<', now()->subYears(2))
                                   ->delete();

        // Clean up temporary contract data
        Contract::where('status', 'draft')
                ->where('created_at', '<', now()->subMonths(6))
                ->delete();
    }

    /**
     * Get job description for monitoring
     */
    public function getJobDescription(): string
    {
        return match($this->operationType) {
            'expiry_processing' => 'Processing contract expirations and renewal notifications',
            'auto_renewal' => 'Processing automatic contract renewals',
            'compliance_monitoring' => 'Running compliance monitoring checks',
            'volume_progression' => 'Processing volume progression and tier achievements',
            'milestone_processing' => 'Processing customer milestone achievements',
            'batch_notification' => 'Sending batch notifications for contracts',
            'system_maintenance' => 'Performing system maintenance tasks',
            default => 'Full contract management processing cycle'
        };
    }
}