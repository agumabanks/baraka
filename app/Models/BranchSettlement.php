<?php

namespace App\Models;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BranchSettlement extends Model
{
    use LogsActivity;

    protected $fillable = [
        'settlement_number',
        'branch_id',
        'period_start',
        'period_end',
        'total_shipment_revenue',
        'total_cod_collected',
        'shipment_count',
        'cod_shipment_count',
        'total_expenses',
        'driver_payments',
        'operational_costs',
        'net_amount',
        'amount_due_to_hq',
        'amount_due_from_hq',
        'currency',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'settled_by',
        'settled_at',
        'payment_method',
        'payment_reference',
        'breakdown',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_shipment_revenue' => 'decimal:2',
        'total_cod_collected' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'driver_payments' => 'decimal:2',
        'operational_costs' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'amount_due_to_hq' => 'decimal:2',
        'amount_due_from_hq' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'settled_at' => 'datetime',
        'breakdown' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('branch_settlement')
            ->logOnly(['status', 'net_amount', 'approved_by', 'settled_by'])
            ->setDescriptionForEvent(fn(string $eventName) => "Branch settlement {$eventName}");
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($settlement) {
            if (!$settlement->settlement_number) {
                $settlement->settlement_number = self::generateSettlementNumber($settlement->branch_id);
            }
        });
    }

    public static function generateSettlementNumber(int $branchId): string
    {
        $prefix = 'BST';
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', today())
            ->where('branch_id', $branchId)
            ->count() + 1;
        
        return sprintf('%s-%d-%s-%04d', $prefix, $branchId, $date, $sequence);
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function settledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingSettlement($query)
    {
        return $query->whereIn('status', ['submitted', 'approved']);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Actions
    public function submit(int $userId): self
    {
        $this->update([
            'status' => 'submitted',
            'submitted_by' => $userId,
            'submitted_at' => now(),
        ]);
        return $this;
    }

    public function approve(int $userId): self
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
        return $this;
    }

    public function reject(int $userId, string $reason): self
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
        return $this;
    }

    public function markSettled(int $userId, string $method, string $reference): self
    {
        $this->update([
            'status' => 'settled',
            'settled_by' => $userId,
            'settled_at' => now(),
            'payment_method' => $method,
            'payment_reference' => $reference,
        ]);
        return $this;
    }

    // Helpers
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'submitted' => 'warning',
            'approved' => 'info',
            'rejected' => 'danger',
            'settled' => 'success',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'settled' => 'Settled',
            default => 'Unknown',
        };
    }

    public function canSubmit(): bool
    {
        return $this->status === 'draft';
    }

    public function canApprove(): bool
    {
        return $this->status === 'submitted';
    }

    public function canSettle(): bool
    {
        return $this->status === 'approved';
    }
}
