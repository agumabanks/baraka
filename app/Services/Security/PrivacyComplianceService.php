<?php

namespace App\Services\Security;

use App\Models\Security\SecurityPrivacyConsent;
use App\Models\Security\SecurityAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class PrivacyComplianceService
{
    private const GDPR_RETENTION_PERIOD = 2555; // 7 years in days
    private const CCPA_RETENTION_PERIOD = 1095; // 3 years in days

    /**
     * Handle data subject request - Right to be forgotten
     */
    public function handleRightToBeForgotten(User $user, array $options = []): array
    {
        try {
            DB::beginTransaction();
            
            $results = [
                'anonymized' => 0,
                'deleted' => 0,
                'retained' => 0,
                'errors' => [],
            ];
            
            // Log the request
            $this->logPrivacyEvent($user, 'right_to_be_forgotten_requested', [
                'request_id' => uniqid(),
                'options' => $options,
            ], 'high');
            
            // Anonymize data where legally required
            if ($options['anonymize'] ?? true) {
                $results['anonymized'] += $this->anonymizeUserData($user, $options);
            }
            
            // Delete data where legally permitted
            if ($options['delete'] ?? false) {
                $results['deleted'] += $this->deleteUserData($user, $options);
            }
            
            // Retain data where legally required
            if ($options['retain'] ?? true) {
                $results['retained'] += $this->retainUserData($user, $options);
            }
            
            // Create audit trail
            $this->createPrivacyAuditTrail($user, 'right_to_be_forgotten_completed', [
                'results' => $results,
                'request_id' => uniqid(),
            ]);
            
            DB::commit();
            
            $this->logPrivacyEvent($user, 'right_to_be_forgotten_completed', $results, 'success');
            
            return $results;
            
        } catch (Exception $e) {
            DB::rollBack();
            $this->logPrivacyEvent($user, 'right_to_be_forgotten_failed', [
                'error' => $e->getMessage(),
            ], 'error');
            throw new Exception('Right to be forgotten request failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle data export request - Right to data portability
     */
    public function handleDataExportRequest(User $user, array $dataTypes = []): array
    {
        try {
            $exportData = [];
            
            // Log the request
            $this->logPrivacyEvent($user, 'data_export_requested', [
                'data_types' => $dataTypes,
                'request_id' => uniqid(),
            ], 'info');
            
            // Export requested data types
            foreach ($dataTypes as $dataType) {
                $exportData[$dataType] = $this->exportUserData($user, $dataType);
            }
            
            // Create audit trail
            $this->createPrivacyAuditTrail($user, 'data_export_completed', [
                'data_types' => $dataTypes,
                'export_id' => uniqid(),
            ]);
            
            $this->logPrivacyEvent($user, 'data_export_completed', [
                'data_types' => $dataTypes,
                'export_size' => sizeof($exportData),
            ], 'success');
            
            return [
                'export_data' => $exportData,
                'export_id' => uniqid(),
                'exported_at' => now(),
            ];
            
        } catch (Exception $e) {
            $this->logPrivacyEvent($user, 'data_export_failed', [
                'error' => $e->getMessage(),
            ], 'error');
            throw new Exception('Data export request failed: ' . $e->getMessage());
        }
    }

    /**
     * Record consent for privacy compliance
     */
    public function recordConsent(User $user, string $consentType, bool $consentGiven, array $metadata = []): SecurityPrivacyConsent
    {
        $consent = SecurityPrivacyConsent::create([
            'user_id' => $user->id,
            'consent_type' => $consentType,
            'consent_given' => $consentGiven,
            'consent_source' => $metadata['source'] ?? 'api',
            'ip_address' => $metadata['ip_address'] ?? request()->ip(),
            'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
            'consent_data' => $metadata,
            'expires_at' => $this->getConsentExpiry($consentType),
        ]);
        
        $this->logPrivacyEvent($user, 'consent_recorded', [
            'consent_type' => $consentType,
            'consent_given' => $consentGiven,
            'consent_id' => $consent->id,
        ], 'low');
        
        return $consent;
    }

    /**
     * Check if user has given consent for a specific purpose
     */
    public function hasConsent(User $user, string $consentType): bool
    {
        return SecurityPrivacyConsent::where('user_id', $user->id)
            ->where('consent_type', $consentType)
            ->active()
            ->exists();
    }

    /**
     * Withdraw consent
     */
    public function withdrawConsent(User $user, string $consentType, string $method = 'user_request'): bool
    {
        try {
            $consents = SecurityPrivacyConsent::where('user_id', $user->id)
                ->where('consent_type', $consentType)
                ->active()
                ->get();
                
            foreach ($consents as $consent) {
                $consent->withdraw($method);
            }
            
            $this->logPrivacyEvent($user, 'consent_withdrawn', [
                'consent_type' => $consentType,
                'withdrawal_method' => $method,
            ], 'medium');
            
            return true;
            
        } catch (Exception $e) {
            $this->logPrivacyEvent($user, 'consent_withdrawal_failed', [
                'consent_type' => $consentType,
                'error' => $e->getMessage(),
            ], 'error');
            return false;
        }
    }

    /**
     * Anonymize user data for GDPR compliance
     */
    private function anonymizeUserData(User $user, array $options): int
    {
        $anonymizedCount = 0;
        
        // Anonymize user profile data
        $user->update([
            'name' => 'Anonymous User',
            'email' => "anonymous+{$user->id}@example.com",
            'phone_e164' => null,
            'address' => null,
        ]);
        $anonymizedCount++;
        
        // Anonymize related data
        if (isset($options['anonymize_shipments']) && $options['anonymize_shipments']) {
            $shipments = Shipment::where('customer_id', $user->id)->get();
            foreach ($shipments as $shipment) {
                $shipment->update([
                    'customer_name' => 'Anonymous Customer',
                    'customer_phone' => null,
                    'customer_address' => null,
                ]);
                $anonymizedCount++;
            }
        }
        
        return $anonymizedCount;
    }

    /**
     * Delete user data where legally permitted
     */
    private function deleteUserData(User $user, array $options): int
    {
        $deletedCount = 0;
        
        // Delete non-essential data
        $deletedRecords = [];
        
        // This would delete specific records based on legal requirements
        // Implementation depends on your data retention policies
        
        return $deletedCount;
    }

    /**
     * Retain user data where legally required
     */
    private function retainUserData(User $user, array $options): int
    {
        $retainedCount = 0;
        
        // Financial data retention (legal requirements)
        $financialData = Payment::where('user_id', $user->id)->get();
        foreach ($financialData as $record) {
            // Mark for retention but anonymize
            $record->update([
                'user_id' => null, // Remove user reference
                'status' => 'retained_anonymized',
            ]);
            $retainedCount++;
        }
        
        return $retainedCount;
    }

    /**
     * Export user data for data portability
     */
    private function exportUserData(User $user, string $dataType): array
    {
        return match($dataType) {
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'shipments' => Shipment::where('customer_id', $user->id)->get()->toArray(),
            'payments' => Payment::where('user_id', $user->id)->get()->toArray(),
            default => [],
        };
    }

    /**
     * Get consent expiry date based on consent type
     */
    private function getConsentExpiry(string $consentType): ?\Carbon\Carbon
    {
        return match($consentType) {
            'marketing' => now()->addYear(),
            'analytics' => now()->addMonths(6),
            'third_party' => now()->addMonths(3),
            'necessary' => null, // Never expires
            default => now()->addYear(),
        };
    }

    /**
     * Create privacy audit trail
     */
    private function createPrivacyAuditTrail(User $user, string $eventType, array $data): void
    {
        SecurityAuditLog::create([
            'event_type' => $eventType,
            'event_category' => 'privacy',
            'severity' => $this->getPrivacyEventSeverity($eventType),
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'resource_type' => 'user_data',
            'action_details' => $data,
            'status' => 'success',
            'description' => "Privacy event: {$eventType}",
        ]);
    }

    /**
     * Get severity for privacy events
     */
    private function getPrivacyEventSeverity(string $eventType): string
    {
        return match($eventType) {
            'right_to_be_forgotten_requested',
            'data_breach_detected' => 'high',
            'consent_withdrawn',
            'data_export_requested' => 'medium',
            'consent_recorded' => 'low',
            default => 'low',
        };
    }

    /**
     * Log privacy event
     */
    private function logPrivacyEvent(User $user, string $event, array $data, string $level = 'info'): void
    {
        $logData = [
            'user_id' => $user->id,
            'event' => $event,
            'data' => $data,
            'level' => $level,
        ];
        
        Log::channel('privacy')->log($level, "Privacy Compliance Event", $logData);
    }

    /**
     * Check data retention compliance
     */
    public function checkDataRetentionCompliance(): array
    {
        $compliance = [
            'gdpr_compliant' => true,
            'ccpa_compliant' => true,
            'violations' => [],
            'recommendations' => [],
        ];
        
        // Check for data older than retention periods
        $gdprRetentionDate = now()->subDays(self::GDPR_RETENTION_PERIOD);
        $ccpaRetentionDate = now()->subDays(self::CCPA_RETENTION_PERIOD);
        
        // Check financial data retention
        $oldFinancialData = Payment::where('created_at', '<', $gdprRetentionDate)
            ->where('status', '!=', 'retained_anonymized')
            ->count();
            
        if ($oldFinancialData > 0) {
            $compliance['violations'][] = "Found {$oldFinancialData} financial records older than GDPR retention period";
            $compliance['gdpr_compliant'] = false;
            $compliance['recommendations'][] = "Review and anonymize or delete old financial data";
        }
        
        return $compliance;
    }
}