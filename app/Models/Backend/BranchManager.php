<?php

namespace App\Models\Backend;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BranchManager extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'branch_id',
        'user_id',
        'business_name',
        'current_balance',
        'cod_charges',
        'payment_info',
        'settlement_config',
        'metadata',
        'status',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'cod_charges' => 'array',
        'payment_info' => 'array',
        'settlement_config' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('BranchManager')
            ->logOnly(['business_name', 'current_balance', 'status'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} branch manager: {$this->business_name}");
    }

    // Relationships

    /**
     * The branch this manager manages
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * The user account for this branch manager
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Shipments created by clients of this branch manager
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'created_by');
    }

    /**
     * Payment requests from this branch manager
     */
    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'branch_manager_id');
    }

    /**
     * Settlements for this branch manager
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class, 'branch_manager_id');
    }

    /**
     * Invoices generated for this branch manager
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'branch_manager_id');
    }

    // Scopes

    /**
     * Scope: Only active branch managers
     */
    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE);
    }

    /**
     * Scope: With positive balance
     */
    public function scopeWithBalance($query)
    {
        return $query->where('current_balance', '>', 0);
    }

    /**
     * Scope: With outstanding payments
     */
    public function scopeWithOutstandingPayments($query)
    {
        return $query->whereHas('paymentRequests', function ($q) {
            $q->where('status', 'pending');
        });
    }

    // Accessors & Mutators

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->current_balance, 2);
    }

    /**
     * Get COD charges as formatted string
     */
    public function getFormattedCodChargesAttribute(): string
    {
        if (!$this->cod_charges) {
            return '';
        }

        $charges = [];
        foreach ($this->cod_charges as $key => $value) {
            $charges[] = __('branch.cod_charge_' . $key) . ': ' . $value;
        }

        return implode(', ', $charges);
    }

    /**
     * Get manager's full name from user relationship
     */
    public function getFullNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'Unknown Manager';
    }

    /**
     * Get manager's email from user relationship
     */
    public function getEmailAttribute(): string
    {
        return $this->user ? $this->user->email : '';
    }

    /**
     * Get branch name
     */
    public function getBranchNameAttribute(): string
    {
        return $this->branch ? $this->branch->name : 'Unassigned';
    }

    // Business Logic Methods

    /**
     * Check if branch manager can create shipments
     */
    public function canCreateShipments(): bool
    {
        return $this->status === Status::ACTIVE &&
               $this->branch &&
               $this->branch->status === Status::ACTIVE;
    }

    /**
     * Get available balance for payments
     */
    public function getAvailableBalance(): float
    {
        // Subtract pending payment requests
        $pendingRequests = $this->paymentRequests()
            ->where('status', 'pending')
            ->sum('amount');

        return max(0, $this->current_balance - $pendingRequests);
    }

    /**
     * Calculate COD charges for a shipment
     */
    public function calculateCodCharges(float $shipmentValue, string $serviceType = 'standard'): float
    {
        if (!$this->cod_charges) {
            return 0.0;
        }

        $charges = 0.0;

        // Base COD charge
        if (isset($this->cod_charges['base'])) {
            $charges += $this->cod_charges['base'];
        }

        // Percentage-based charge
        if (isset($this->cod_charges['percentage'])) {
            $charges += ($shipmentValue * $this->cod_charges['percentage'] / 100);
        }

        // Service-specific charges
        $serviceKey = 'service_' . $serviceType;
        if (isset($this->cod_charges[$serviceKey])) {
            $charges += $this->cod_charges[$serviceKey];
        }

        return round($charges, 2);
    }

    /**
     * Get settlement summary
     */
    public function getSettlementSummary(): array
    {
        $totalShipments = $this->shipments()->count();
        $totalRevenue = $this->shipments()->sum('price_amount');
        $totalPaid = $this->settlements()->where('status', 'completed')->sum('amount');
        $pendingPayments = $this->paymentRequests()->where('status', 'pending')->sum('amount');

        return [
            'total_shipments' => $totalShipments,
            'total_revenue' => $totalRevenue,
            'total_settled' => $totalPaid,
            'pending_payments' => $pendingPayments,
            'available_balance' => $this->getAvailableBalance(),
            'outstanding_balance' => $this->current_balance,
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $recentShipments = $this->shipments()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $successfulDeliveries = $this->shipments()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->where('status', 'delivered')
            ->count();

        $onTimeDeliveries = $this->shipments()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->where('status', 'delivered')
            ->whereRaw('delivered_at <= expected_delivery_date')
            ->count();

        $totalRevenue = $this->shipments()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('price_amount');

        return [
            'shipments_last_30_days' => $recentShipments,
            'delivery_success_rate' => $recentShipments > 0 ? ($successfulDeliveries / $recentShipments) * 100 : 0,
            'on_time_delivery_rate' => $successfulDeliveries > 0 ? ($onTimeDeliveries / $successfulDeliveries) * 100 : 0,
            'revenue_last_30_days' => $totalRevenue,
            'average_shipment_value' => $recentShipments > 0 ? $totalRevenue / $recentShipments : 0,
        ];
    }

    /**
     * Check if branch manager has permission for an action
     */
    public function hasPermission(string $permission): bool
    {
        // Check user role permissions
        if (!$this->user) {
            return false;
        }

        return $this->user->hasRole('branch_manager') &&
               $this->user->hasPermission($permission);
    }

    /**
     * Get branch manager's dashboard data
     */
    public function getDashboardData(): array
    {
        return [
            'manager_info' => [
                'name' => $this->full_name,
                'email' => $this->email,
                'branch' => $this->branch_name,
                'business_name' => $this->business_name,
            ],
            'financial_summary' => $this->getSettlementSummary(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'recent_shipments' => $this->shipments()
                ->with(['originBranch', 'destBranch'])
                ->latest()
                ->take(10)
                ->get(),
            'pending_requests' => $this->paymentRequests()
                ->where('status', 'pending')
                ->get(),
        ];
    }

    /**
     * Update balance with proper logging
     */
    public function updateBalance(float $amount, string $type, string $description = null): bool
    {
        $oldBalance = $this->current_balance;

        if ($type === 'credit') {
            $this->current_balance += $amount;
        } elseif ($type === 'debit') {
            $this->current_balance -= $amount;
        } else {
            return false;
        }

        $this->save();

        // Log the balance change
        activity()
            ->performedOn($this)
            ->withProperties([
                'old_balance' => $oldBalance,
                'new_balance' => $this->current_balance,
                'change_amount' => $amount,
                'change_type' => $type,
                'description' => $description,
            ])
            ->log("Balance {$type}: {$amount}");

        return true;
    }

    /**
     * Get status badge for UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            Status::ACTIVE => '<span class="badge badge-success">Active</span>',
            Status::INACTIVE => '<span class="badge badge-danger">Inactive</span>',
            default => '<span class="badge badge-secondary">Unknown</span>',
        };
    }
}
