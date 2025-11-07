<?php

namespace App\Services\Security;

use App\Models\Security\SecurityAuditLog;
use App\Models\Security\SecurityRole;
use App\Models\User;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class FinancialSecurityService
{
    private const FINANCIAL_TRANSACTION_MIN = 100; // Minimum amount for enhanced tracking
    private const APPROVAL_THRESHOLD = 1000; // Amount requiring approval
    private const SEGREGATION_ROLES = [
        'payment_creator' => 'finance_user',
        'payment_approver' => 'finance_manager',
        'payment_processor' => 'finance_admin',
    ];

    /**
     * Create financial transaction with security controls
     */
    public function createFinancialTransaction(User $user, array $data): array
    {
        try {
            DB::beginTransaction();
            
            // Check segregation of duties
            $this->enforceSegregationOfDuties($user, 'create');
            
            // Log transaction attempt
            $this->logFinancialEvent($user, 'transaction_create_attempt', $data, 'info');
            
            // Encrypt sensitive financial data
            $encryptedData = $this->encryptFinancialData($data);
            
            // Create audit trail
            $this->createFinancialAuditTrail($user, 'transaction_created', $data, $encryptedData);
            
            // Check for approval requirements
            $requiresApproval = $this->checkApprovalRequired($data['amount'] ?? 0);
            
            if ($requiresApproval) {
                $this->requestApproval($user, $data, 'transaction_approval');
                $transaction = $this->createPendingTransaction($encryptedData, $user);
            } else {
                $transaction = $this->processTransaction($encryptedData, $user);
            }
            
            DB::commit();
            
            // Log successful creation
            $this->logFinancialEvent($user, 'transaction_created', [
                'transaction_id' => $transaction->id,
                'amount' => $data['amount'] ?? 0,
                'requires_approval' => $requiresApproval,
            ], 'success');
            
            return [
                'success' => true,
                'transaction' => $transaction,
                'requires_approval' => $requiresApproval,
                'transaction_id' => $transaction->id,
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            $this->logFinancialEvent($user, 'transaction_creation_failed', $data, 'error', $e->getMessage());
            throw new Exception('Failed to create financial transaction: ' . $e->getMessage());
        }
    }

    /**
     * Approve financial transaction
     */
    public function approveTransaction(User $approver, int $transactionId, array $approvalData = []): bool
    {
        try {
            DB::beginTransaction();
            
            // Verify approver has approval permissions
            if (!$this->hasApprovalPermission($approver)) {
                throw new Exception('Insufficient permissions for approval');
            }
            
            // Log approval attempt
            $this->logFinancialEvent($approver, 'transaction_approval_attempt', [
                'transaction_id' => $transactionId,
                'approval_data' => $approvalData,
            ], 'info');
            
            // Update transaction status
            $transaction = $this->updateTransactionStatus($transactionId, 'approved', $approver, $approvalData);
            
            // Create final audit trail
            $this->createFinancialAuditTrail($approver, 'transaction_approved', [
                'transaction_id' => $transactionId,
                'approval_data' => $approvalData,
            ]);
            
            // Process the approved transaction
            $this->processTransaction($transaction->encrypted_data, $approver, $transactionId);
            
            DB::commit();
            
            $this->logFinancialEvent($approver, 'transaction_approved', [
                'transaction_id' => $transactionId,
            ], 'success');
            
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            $this->logFinancialEvent($approver, 'transaction_approval_failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ], 'error');
            
            throw new Exception('Transaction approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Enforce segregation of duties
     */
    private function enforceSegregationOfDuties(User $user, string $action): void
    {
        // Check if user is trying to perform conflicting actions
        $recentActions = SecurityAuditLog::where('user_id', $user->id)
            ->where('event_category', 'financial')
            ->where('created_at', '>', now()->subDay())
            ->whereIn('event_type', ['transaction_approved', 'transaction_processed'])
            ->count();
            
        if ($action === 'create' && $recentActions > 0) {
            // User recently approved/processed, check if they can also create
            if (!$this->canPerformConflictingActions($user, 'create', 'approve')) {
                throw new Exception('Segregation of duties violation: Cannot create and approve in same time period');
            }
        }
        
        if ($action === 'approve' && $recentActions > 2) {
            throw new Exception('Segregation of duties violation: Too many approvals today');
        }
    }

    /**
     * Check if user can perform conflicting actions
     */
    private function canPerformConflictingActions(User $user, string $action1, string $action2): bool
    {
        // This would check against a policy table or configuration
        // For now, return based on user role
        $userRoles = $user->roles ?? [];
        $conflictingActions = [
            'payment_creator' => ['approve', 'process'],
            'payment_approver' => ['create', 'process'],
            'payment_processor' => ['create', 'approve'],
        ];
        
        foreach ($userRoles as $role) {
            if (isset($conflictingActions[$role])) {
                $roleActions = $conflictingActions[$role];
                if (in_array($action2, $roleActions)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Check if transaction requires approval
     */
    private function checkApprovalRequired(float $amount): bool
    {
        return $amount >= self::APPROVAL_THRESHOLD;
    }

    /**
     * Encrypt financial data
     */
    private function encryptFinancialData(array $data): string
    {
        // Use the encryption service to encrypt sensitive financial data
        $encryptionService = new EncryptionService();
        return $encryptionService->encryptFinancialData($data);
    }

    /**
     * Create financial audit trail
     */
    private function createFinancialAuditTrail(User $user, string $eventType, array $data, ?string $encryptedData = null): void
    {
        $sensitiveFields = ['amount', 'account_number', 'iban', 'credit_card', 'ssn'];
        $redactedData = $this->redactSensitiveFields($data, $sensitiveFields);
        
        SecurityAuditLog::create([
            'event_type' => $eventType,
            'event_category' => 'financial',
            'severity' => $this->getFinancialEventSeverity($eventType, $data),
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'resource_type' => 'financial_transaction',
            'action_details' => $redactedData,
            'old_values' => $encryptedData ? ['encrypted_data' => 'present'] : null,
            'new_values' => $encryptedData ? ['encrypted_data' => $encryptedData] : null,
            'status' => 'success',
            'description' => "Financial event: {$eventType}",
        ]);
    }

    /**
     * Get severity for financial events
     */
    private function getFinancialEventSeverity(string $eventType, array $data): string
    {
        $amount = $data['amount'] ?? 0;
        
        if ($amount >= 10000) {
            return 'critical';
        } elseif ($amount >= 1000) {
            return 'high';
        } elseif ($amount >= 100) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Redact sensitive fields
     */
    private function redactSensitiveFields(array $data, array $sensitiveFields): array
    {
        $redacted = $data;
        
        foreach ($sensitiveFields as $field) {
            if (isset($redacted[$field])) {
                $redacted[$field] = $this->redactValue($redacted[$field]);
            }
        }
        
        return $redacted;
    }

    /**
     * Redact value for logging
     */
    private function redactValue(string $value): string
    {
        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }
        
        return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
    }

    /**
     * Log financial event
     */
    private function logFinancialEvent(User $user, string $event, array $data, string $level = 'info', ?string $error = null): void
    {
        $logData = [
            'user_id' => $user->id,
            'event' => $event,
            'data' => $this->redactSensitiveFields($data, ['amount', 'account_number', 'iban']),
            'level' => $level,
        ];
        
        if ($error) {
            $logData['error'] = $error;
        }
        
        Log::channel('financial')->log($level, "Financial Security Event", $logData);
    }

    /**
     * Create pending transaction
     */
    private function createPendingTransaction(string $encryptedData, User $user): Payment
    {
        // This would create a Payment record with pending status
        // For now, return a mock object
        return new Payment([
            'id' => rand(1000, 9999),
            'status' => 'pending_approval',
            'encrypted_data' => $encryptedData,
            'created_by' => $user->id,
        ]);
    }

    /**
     * Process transaction
     */
    private function processTransaction(string $encryptedData, User $user, ?int $transactionId = null): Payment
    {
        // Decrypt and process the transaction
        $encryptionService = new EncryptionService();
        $data = $encryptionService->decryptFinancialData($encryptedData);
        
        // Create the actual payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'processed_at' => now(),
        ]);
        
        return $payment;
    }

    /**
     * Request approval
     */
    private function requestApproval(User $user, array $data, string $approvalType): void
    {
        // Create approval request
        $approvalRequest = [
            'type' => $approvalType,
            'requester_id' => $user->id,
            'data' => $this->redactSensitiveFields($data, ['amount', 'account_number']),
            'amount' => $data['amount'],
            'created_at' => now(),
        ];
        
        // Log approval request
        $this->logFinancialEvent($user, 'approval_requested', $approvalRequest, 'info');
        
        // In a real implementation, this would send notifications to approvers
    }

    /**
     * Check approval permission
     */
    private function hasApprovalPermission(User $user): bool
    {
        // Check if user has finance manager or finance admin role
        return $user->hasRole(['finance_manager', 'finance_admin']);
    }

    /**
     * Update transaction status
     */
    private function updateTransactionStatus(int $transactionId, string $status, User $approver, array $approvalData): Payment
    {
        $payment = Payment::findOrFail($transactionId);
        
        $payment->update([
            'status' => $status,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_data' => $approvalData,
        ]);
        
        return $payment;
    }

    /**
     * Get financial audit trail for a user
     */
    public function getUserFinancialAuditTrail(User $user, ?string $startDate = null, ?string $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = SecurityAuditLog::where('user_id', $user->id)
            ->where('event_category', 'financial');
            
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get financial security metrics
     */
    public function getFinancialSecurityMetrics(string $period = '30days'): array
    {
        $startDate = match($period) {
            '24hours' => now()->subDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(30),
        };
        
        return [
            'total_transactions' => SecurityAuditLog::where('event_category', 'financial')
                ->where('event_type', 'transaction_created')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'total_amount' => SecurityAuditLog::where('event_category', 'financial')
                ->where('event_type', 'transaction_created')
                ->where('created_at', '>=', $startDate)
                ->sum('action_details->amount'),
            'pending_approvals' => SecurityAuditLog::where('event_category', 'financial')
                ->where('event_type', 'approval_requested')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'security_violations' => SecurityAuditLog::where('event_category', 'financial')
                ->where('severity', '>=', 'medium')
                ->where('created_at', '>=', $startDate)
                ->count(),
        ];
    }
}