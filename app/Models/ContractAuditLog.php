<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class ContractAuditLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'contract_id',
        'user_id',
        'action_type',
        'entity_type',
        'entity_id',
        'field_name',
        'old_value',
        'new_value',
        'change_description',
        'ip_address',
        'user_agent',
        'session_id',
        'additional_data',
        'compliance_relevant',
        'approval_required',
        'approved_by',
        'approved_at',
        'rejection_reason'
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
        'additional_data' => 'array',
        'compliance_relevant' => 'boolean',
        'approval_required' => 'boolean',
        'approved_at' => 'datetime',
        'user_id' => 'integer',
        'contract_id' => 'integer',
        'entity_id' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ContractAuditLog')
            ->logOnly(['action_type', 'entity_type', 'field_name', 'change_description'])
            ->setDescriptionForEvent(fn (string $eventName) => "Contract audit log {$eventName}");
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeByAction($query, string $action)
    {
        return $query->where('action_type', $action);
    }

    public function scopeByEntity($query, string $entityType, ?int $entityId = null)
    {
        $query = $query->where('entity_type', $entityType);
        
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        
        return $query;
    }

    public function scopeByField($query, string $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }

    public function scopeComplianceRelevant($query)
    {
        return $query->where('compliance_relevant', true);
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('approval_required', true)
                    ->whereNull('approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_by');
    }

    public function scopeRejected($query)
    {
        return $query->whereNotNull('rejection_reason');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Business Logic
    public function approve(int $approvedByUserId): bool
    {
        return $this->update([
            'approved_by' => $approvedByUserId,
            'approved_at' => now()
        ]);
    }

    public function reject(int $rejectedByUserId, string $reason): bool
    {
        return $this->update([
            'approved_by' => $rejectedByUserId,
            'rejection_reason' => $reason
        ]);
    }

    public function isApprovalRequired(): bool
    {
        return $this->approval_required && is_null($this->approved_by) && is_null($this->rejection_reason);
    }

    public function isPending(): bool
    {
        return $this->isApprovalRequired();
    }

    public function isApproved(): bool
    {
        return !is_null($this->approved_by) && is_null($this->rejection_reason);
    }

    public function isRejected(): bool
    {
        return !is_null($this->rejection_reason);
    }

    public function getChangeSummary(): array
    {
        $summary = [
            'action' => $this->action_type,
            'entity' => $this->entity_type,
            'description' => $this->change_description,
            'timestamp' => $this->created_at->toISOString(),
            'user' => $this->user?->name ?? 'System',
            'compliance_relevant' => $this->compliance_relevant,
            'approval_status' => $this->getApprovalStatus()
        ];

        if ($this->field_name) {
            $summary['field'] = $this->field_name;
            
            if ($this->old_value !== null || $this->new_value !== null) {
                $summary['old_value'] = $this->old_value;
                $summary['new_value'] = $this->new_value;
            }
        }

        if ($this->isPending()) {
            $summary['requires_approval'] = true;
        }

        return $summary;
    }

    public function getApprovalStatus(): string
    {
        if ($this->isApproved()) {
            return 'approved';
        } elseif ($this->isRejected()) {
            return 'rejected';
        } elseif ($this->isPending()) {
            return 'pending';
        } elseif (!$this->approval_required) {
            return 'auto_approved';
        }
        
        return 'unknown';
    }

    public static function logChange(
        int $contractId,
        string $actionType,
        string $entityType,
        ?int $entityId = null,
        ?string $fieldName = null,
        $oldValue = null,
        $newValue = null,
        ?string $description = null,
        array $additionalData = []
    ): self {
        return self::create([
            'contract_id' => $contractId,
            'user_id' => auth()->id(),
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'change_description' => $description ?? self::generateDescription($actionType, $entityType, $fieldName, $oldValue, $newValue),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'additional_data' => $additionalData,
            'compliance_relevant' => self::isComplianceRelevant($actionType, $entityType, $fieldName),
            'approval_required' => self::requiresApproval($actionType, $entityType, $fieldName)
        ]);
    }

    public static function logApprovalRequest(
        int $contractId,
        string $actionType,
        string $entityType,
        array $changeData,
        ?string $description = null
    ): self {
        return self::logChange(
            $contractId,
            $actionType,
            $entityType,
            null,
            null,
            null,
            null,
            $description,
            array_merge($changeData, ['approval_requested_at' => now()->toISOString()])
        );
    }

    private static function generateDescription(string $action, string $entity, ?string $field, $oldValue, $newValue): string
    {
        $actionDescriptions = [
            'created' => "Created new {$entity}",
            'updated' => $field ? "Updated {$entity} {$field}" : "Updated {$entity}",
            'deleted' => "Deleted {$entity}",
            'activated' => "Activated {$entity}",
            'deactivated' => "Deactivated {$entity}",
            'approved' => "Approved {$entity}",
            'rejected' => "Rejected {$entity}",
            'renewed' => "Renewed {$entity}",
            'amended' => "Amended {$entity}"
        ];

        $baseDescription = $actionDescriptions[$action] ?? "Performed {$action} on {$entity}";

        if ($field && $action === 'updated' && $oldValue !== null && $newValue !== null) {
            $baseDescription .= " from '{$oldValue}' to '{$newValue}'";
        }

        return $baseDescription;
    }

    private static function isComplianceRelevant(string $action, string $entity, ?string $field): bool
    {
        $complianceFields = [
            'price', 'rate', 'discount', 'volume_commitment', 'service_level',
            'delivery_terms', 'payment_terms', 'liability', 'termination_clause'
        ];

        $complianceEntities = ['contract', 'pricing', 'sla', 'terms'];

        return in_array($entity, $complianceEntities) || 
               in_array($field, $complianceFields) ||
               in_array($action, ['approved', 'rejected', 'amended']);
    }

    private static function requiresApproval(string $action, string $entity, ?string $field): bool
    {
        $highValueFields = ['price', 'rate', 'discount', 'volume_commitment'];
        $criticalEntities = ['contract', 'pricing'];
        $approvalRequiredActions = ['approved', 'rejected', 'amended'];

        return in_array($field, $highValueFields) ||
               in_array($entity, $criticalEntities) ||
               in_array($action, $approvalRequiredActions);
    }

    public static function getContractHistory(int $contractId, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('contract_id', $contractId)
                  ->with(['user', 'approver'])
                  ->orderBy('created_at', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public static function getComplianceChanges(int $contractId, int $days = 365): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('contract_id', $contractId)
                  ->where('compliance_relevant', true)
                  ->where('created_at', '>=', now()->subDays($days))
                  ->with(['user'])
                  ->orderBy('created_at', 'desc')
                  ->get();
    }

    public static function getPendingApprovals(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('approval_required', true)
                  ->whereNull('approved_by')
                  ->with(['contract.customer', 'user'])
                  ->orderBy('created_at', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public function getFormattedChange(): string
    {
        $user = $this->user?->name ?? 'System';
        $timestamp = $this->created_at->format('Y-m-d H:i:s');
        
        $change = "{$timestamp} - {$user}: {$this->change_description}";
        
        if ($this->field_name && $this->old_value !== null && $this->new_value !== null) {
            $change .= " [{$this->field_name}: '{$this->old_value}' → '{$this->new_value}']";
        }
        
        return $change;
    }
}