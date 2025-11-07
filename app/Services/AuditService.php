<?php

namespace App\Services;

use App\Models\AuditTrailLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuditService
{
    /**
     * Log a user action for audit trail
     */
    public function logAction(
        string $actionType,
        string $resourceType,
        ?string $resourceId = null,
        array $oldValues = null,
        array $newValues = null,
        array $metadata = [],
        string $severity = 'info',
        string $module = 'frontend',
        ?string $transactionId = null
    ): AuditTrailLog {
        $user = auth()->user();
        $sessionId = session()->getId();
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        // Determine if action is reversible
        $isReversible = in_array($actionType, ['create', 'update', 'delete']) && $oldValues !== null;

        // Calculate changed fields
        $changedFields = null;
        if ($oldValues && $newValues) {
            $changedFields = $this->calculateChangedFields($oldValues, $newValues);
        }

        $auditLog = AuditTrailLog::create([
            'log_id' => $this->generateLogId(),
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'action_type' => $actionType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'module' => $module,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'severity' => $severity,
            'metadata' => $metadata,
            'transaction_id' => $transactionId ?? $this->generateTransactionId(),
            'occurred_at' => now(),
            'is_reversible' => $isReversible,
        ]);

        return $auditLog;
    }

    /**
     * Log user authentication events
     */
    public function logAuthentication(
        string $action, // 'login', 'logout', 'failed_login', 'password_change'
        array $metadata = [],
        string $severity = 'info'
    ): AuditTrailLog {
        $user = auth()->user();
        
        return $this->logAction(
            actionType: $action,
            resourceType: 'authentication',
            resourceId: $user?->id,
            metadata: array_merge($metadata, [
                'login_method' => request()->header('X-Auth-Method', 'web'),
                'remember_me' => request()->has('remember'),
            ]),
            severity: $severity,
            module: 'auth'
        );
    }

    /**
     * Log pricing-related actions
     */
    public function logPricingAction(
        string $actionType,
        array $pricingData,
        array $oldData = null,
        string $module = 'api'
    ): AuditTrailLog {
        $metadata = [
            'calculation_method' => $pricingData['calculation_method'] ?? null,
            'total_amount' => $pricingData['total_amount'] ?? null,
            'currency' => $pricingData['currency'] ?? 'USD',
            'pricing_rules_applied' => $pricingData['rules_applied'] ?? [],
        ];

        return $this->logAction(
            actionType: $actionType,
            resourceType: 'pricing',
            resourceId: $pricingData['quote_id'] ?? null,
            oldValues: $oldData,
            newValues: $pricingData,
            metadata: $metadata,
            module: $module
        );
    }

    /**
     * Log contract-related actions
     */
    public function logContractAction(
        string $actionType,
        array $contractData,
        array $oldData = null,
        string $module = 'api'
    ): AuditTrailLog {
        $metadata = [
            'contract_type' => $contractData['contract_type'] ?? null,
            'effective_date' => $contractData['effective_date'] ?? null,
            'expiration_date' => $contractData['expiration_date'] ?? null,
            'total_value' => $contractData['total_value'] ?? null,
        ];

        return $this->logAction(
            actionType: $actionType,
            resourceType: 'contract',
            resourceId: $contractData['contract_id'] ?? null,
            oldValues: $oldData,
            newValues: $contractData,
            metadata: $metadata,
            module: $module
        );
    }

    /**
     * Log compliance-related actions
     */
    public function logComplianceAction(
        string $actionType,
        string $complianceFramework,
        array $complianceData,
        string $severity = 'warning',
        string $module = 'compliance'
    ): AuditTrailLog {
        $metadata = [
            'framework' => $complianceFramework,
            'compliance_type' => $complianceData['type'] ?? null,
            'affected_records' => $complianceData['affected_records'] ?? [],
            'auto_resolved' => $complianceData['auto_resolved'] ?? false,
        ];

        return $this->logAction(
            actionType: $actionType,
            resourceType: 'compliance',
            metadata: $metadata,
            severity: $severity,
            module: $module
        );
    }

    /**
     * Log API requests for audit trail
     */
    public function logApiRequest(
        string $method,
        string $endpoint,
        array $requestData = [],
        array $responseData = null,
        int $responseCode = 200,
        float $duration = 0
    ): AuditTrailLog {
        $user = auth()->user();

        $metadata = [
            'http_method' => $method,
            'endpoint' => $endpoint,
            'response_code' => $responseCode,
            'request_duration_ms' => round($duration * 1000, 2),
            'request_size' => strlen(json_encode($requestData)),
            'response_size' => $responseData ? strlen(json_encode($responseData)) : 0,
        ];

        return $this->logAction(
            actionType: 'api_request',
            resourceType: 'api',
            resourceId: $endpoint,
            newValues: $requestData,
            metadata: $metadata,
            module: 'api'
        );
    }

    /**
     * Get audit logs with advanced filtering
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50)
    {
        $query = AuditTrailLog::query();

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (!empty($filters['resource_type'])) {
            $query->forResource($filters['resource_type'], $filters['resource_id'] ?? null);
        }

        if (!empty($filters['action_type'])) {
            $query->forAction($filters['action_type']);
        }

        if (!empty($filters['severity'])) {
            $query->forSeverity($filters['severity']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('occurred_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('occurred_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('action_type', 'like', "%{$filters['search']}%")
                  ->orWhere('resource_type', 'like', "%{$filters['search']}%")
                  ->orWhere('metadata', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('occurred_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport(array $filters = [])
    {
        $query = AuditTrailLog::query();

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->where('occurred_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('occurred_at', '<=', $filters['date_to']);
        }

        $logs = $query->get();

        return [
            'total_actions' => $logs->count(),
            'actions_by_type' => $logs->groupBy('action_type')->map->count(),
            'actions_by_severity' => $logs->groupBy('severity')->map->count(),
            'actions_by_module' => $logs->groupBy('module')->map->count(),
            'critical_actions' => $logs->where('severity', 'critical')->count(),
            'user_activity' => $logs->groupBy('user_id')->map->count(),
            'resource_activities' => $logs->groupBy('resource_type')->map->count(),
            'daily_activity' => $logs->groupBy(function ($log) {
                return $log->occurred_at->format('Y-m-d');
            })->map->count(),
        ];
    }

    /**
     * Clean up old audit logs based on retention policy
     */
    public function cleanupOldLogs(int $retentionDays = 365)
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        $deletedCount = AuditTrailLog::where('occurred_at', '<', $cutoffDate)
                                   ->where('severity', '!=', 'critical')
                                   ->delete();

        return $deletedCount;
    }

    /**
     * Calculate changed fields between old and new values
     */
    private function calculateChangedFields(array $oldValues, array $newValues): array
    {
        $changedFields = [];

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changedFields[] = $key;
            }
        }

        return $changedFields;
    }

    /**
     * Generate unique log ID
     */
    private function generateLogId(): string
    {
        return 'audit_' . now()->format('Y-m-d_H-i-s') . '_' . Str::random(8);
    }

    /**
     * Generate transaction ID for grouping related operations
     */
    private function generateTransactionId(): string
    {
        return 'txn_' . Str::uuid();
    }
}