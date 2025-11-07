<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class ContractAmendment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'contract_id',
        'amendment_number',
        'amendment_type',
        'title',
        'description',
        'amendment_date',
        'effective_date',
        'termination_date',
        'status',
        'requested_by',
        'approved_by',
        'rejected_by',
        'approval_date',
        'rejection_reason',
        'amendment_data',
        'price_changes',
        'terms_changes',
        'scope_changes',
        'created_by',
        'is_major_amendment',
        'requires_customer_approval',
        'customer_approved_at',
        'legal_review_required',
        'legal_reviewed_by',
        'legal_review_date',
        'impact_assessment',
        'implementation_notes',
        'original_contract_snapshot'
    ];

    protected $casts = [
        'amendment_date' => 'date',
        'effective_date' => 'date',
        'termination_date' => 'date',
        'approval_date' => 'datetime',
        'customer_approved_at' => 'datetime',
        'legal_review_date' => 'datetime',
        'amendment_data' => 'array',
        'price_changes' => 'array',
        'terms_changes' => 'array',
        'scope_changes' => 'array',
        'impact_assessment' => 'array',
        'implementation_notes' => 'array',
        'original_contract_snapshot' => 'array',
        'is_major_amendment' => 'boolean',
        'requires_customer_approval' => 'boolean',
        'legal_review_required' => 'boolean',
        'requested_by' => 'integer',
        'approved_by' => 'integer',
        'rejected_by' => 'integer',
        'created_by' => 'integer',
        'legal_reviewed_by' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ContractAmendment')
            ->logOnly(['amendment_number', 'amendment_type', 'title', 'status', 'effective_date'])
            ->setDescriptionForEvent(fn (string $eventName) => "Contract amendment {$eventName}");
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function legalReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'legal_reviewed_by');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('amendment_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeMajorAmendments($query)
    {
        return $query->where('is_major_amendment', true);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeEffective($query)
    {
        return $query->where('status', 'effective')
                    ->where('effective_date', '<=', now()->toDateString())
                    ->where(function($q) {
                        $q->whereNull('termination_date')
                          ->orWhere('termination_date', '>=', now()->toDateString());
                    });
    }

    public function scopeAwaitingCustomerApproval($query)
    {
        return $query->where('requires_customer_approval', true)
                    ->whereNull('customer_approved_at');
    }

    public function scopeRequiringLegalReview($query)
    {
        return $query->where('legal_review_required', true)
                    ->whereNull('legal_reviewed_by');
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('amendment_date', [$startDate, $endDate]);
    }

    // Business Logic
    public function approve(int $approvedByUserId, ?string $notes = null): bool
    {
        $updateData = [
            'status' => 'approved',
            'approved_by' => $approvedByUserId,
            'approval_date' => now()
        ];

        if ($notes) {
            $updateData['implementation_notes'] = array_merge(
                $this->implementation_notes ?? [],
                ['approval_notes' => $notes, 'approved_at' => now()->toISOString()]
            );
        }

        return $this->update($updateData);
    }

    public function reject(int $rejectedByUserId, string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejected_by' => $rejectedByUserId,
            'rejection_reason' => $reason
        ]);
    }

    public function markAsEffective(): bool
    {
        if ($this->status !== 'approved' && $this->status !== 'pending_approval') {
            return false;
        }

        return $this->update([
            'status' => 'effective',
            'effective_date' => now()->toDateString()
        ]);
    }

    public function terminate(?Carbon $terminationDate = null): bool
    {
        $terminationDate = $terminationDate ?? now();
        
        return $this->update([
            'status' => 'terminated',
            'termination_date' => $terminationDate
        ]);
    }

    public function markLegalReviewed(int $reviewedByUserId, array $reviewNotes = []): bool
    {
        return $this->update([
            'legal_reviewed_by' => $reviewedByUserId,
            'legal_review_date' => now(),
            'implementation_notes' => array_merge(
                $this->implementation_notes ?? [],
                ['legal_review' => $reviewNotes]
            )
        ]);
    }

    public function markCustomerApproved(): bool
    {
        return $this->update([
            'customer_approved_at' => now()
        ]);
    }

    public function isEffective(): bool
    {
        return $this->status === 'effective' &&
               $this->effective_date->isPast() &&
               ($this->termination_date === null || $this->termination_date->isFuture());
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function requiresLegalReview(): bool
    {
        return $this->legal_review_required && is_null($this->legal_reviewed_by);
    }

    public function requiresCustomerApproval(): bool
    {
        return $this->requires_customer_approval && is_null($this->customer_approved_at);
    }

    public function getAmendmentsummary(): array
    {
        return [
            'id' => $this->id,
            'amendment_number' => $this->amendment_number,
            'amendment_type' => $this->amendment_type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'amendment_date' => $this->amendment_date->toISOString(),
            'effective_date' => $this->effective_date?->toISOString(),
            'is_major' => $this->is_major_amendment,
            'requires_customer_approval' => $this->requires_customer_approval,
            'customer_approved' => !is_null($this->customer_approved_at),
            'legal_review_required' => $this->legal_review_required,
            'legal_reviewed' => !is_null($this->legal_reviewed_by),
            'change_summary' => $this->getChangeSummary(),
            'impact_assessment' => $this->impact_assessment ?? []
        ];
    }

    public function getChangeSummary(): array
    {
        $summary = [
            'price_changes' => $this->price_changes ?? [],
            'terms_changes' => $this->terms_changes ?? [],
            'scope_changes' => $this->scope_changes ?? []
        ];

        $totalPriceChange = 0;
        foreach ($summary['price_changes'] as $change) {
            $totalPriceChange += $change['amount'] ?? 0;
        }

        $summary['total_price_impact'] = $totalPriceChange;
        $summary['change_count'] = count($summary['price_changes']) + 
                                  count($summary['terms_changes']) + 
                                  count($summary['scope_changes']);

        return $summary;
    }

    public function generateAmendmentNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $contractPrefix = $this->contract->contract_number ?? 'CONT';
        $existingAmendments = self::where('contract_id', $this->contract_id)->count();
        
        return "{$contractPrefix}-AM{$year}{$month}-" . str_pad($existingAmendments + 1, 3, '0', STR_PAD_LEFT);
    }

    public static function createAmendment(
        Contract $contract,
        string $type,
        string $title,
        string $description,
        array $amendmentData,
        int $createdByUserId,
        bool $isMajorAmendment = false
    ): self {
        $amendment = self::create([
            'contract_id' => $contract->id,
            'amendment_type' => $type,
            'title' => $title,
            'description' => $description,
            'amendment_date' => now(),
            'status' => 'draft',
            'created_by' => $createdByUserId,
            'amendment_data' => $amendmentData,
            'is_major_amendment' => $isMajorAmendment,
            'original_contract_snapshot' => $contract->toArray(),
            'legal_review_required' => $isMajorAmendment
        ]);

        // Generate amendment number
        $amendment->update(['amendment_number' => $amendment->generateAmendmentNumber()]);

        return $amendment;
    }

    public static function getContractAmendments(int $contractId, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('contract_id', $contractId)
                    ->with(['requester', 'approver', 'creator']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('amendment_date', 'desc')->get();
    }

    public static function getPendingAmendments(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereIn('status', ['pending_approval', 'pending_customer_approval', 'pending_legal_review'])
                  ->with(['contract.customer', 'requester', 'creator'])
                  ->orderBy('amendment_date', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public function getFinancialImpact(): array
    {
        $priceChanges = $this->price_changes ?? [];
        $totalImpact = 0;
        $monthlyImpact = 0;
        $annualImpact = 0;

        foreach ($priceChanges as $change) {
            $amount = $change['amount'] ?? 0;
            $frequency = $change['frequency'] ?? 'one_time';
            
            $totalImpact += $amount;
            
            if ($frequency === 'monthly') {
                $monthlyImpact += $amount;
                $annualImpact += $amount * 12;
            } elseif ($frequency === 'annual') {
                $annualImpact += $amount;
            }
        }

        return [
            'total_impact' => $totalImpact,
            'monthly_impact' => $monthlyImpact,
            'annual_impact' => $annualImpact,
            'change_details' => $priceChanges
        ];
    }
}