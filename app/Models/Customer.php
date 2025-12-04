<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Backend\Branch;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
class Customer extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, LogsActivity, Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'customer_code',
        'company_name',
        'contact_person',
        'email',
        'password',
        'phone',
        'mobile',
        'fax',
        'billing_address',
        'shipping_address',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'registration_number',
        'industry',
        'company_size',
        'annual_revenue',
        'credit_limit',
        'current_balance',
        'payment_terms',
        'discount_rate',
        'currency',
        'customer_type',
        'segment',
        'source',
        'priority_level',
        'communication_channels',
        'notification_preferences',
        'preferred_language',
        'account_manager_id',
        'primary_branch_id',
        'created_by_branch_id',
        'created_by_user_id',
        'branch_id',
        'sales_rep_id',
        'status',
        'last_contact_date',
        'last_shipment_date',
        'customer_since',
        'notes',
        'total_shipments',
        'total_spent',
        'average_order_value',
        'complaints_count',
        'satisfaction_score',
        'kyc_verified',
        'kyc_verified_at',
        'compliance_flags',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'annual_revenue' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'satisfaction_score' => 'decimal:2',
        'communication_channels' => 'array',
        'notification_preferences' => 'array',
        'compliance_flags' => 'array',
        'customer_since' => 'datetime',
        'last_contact_date' => 'datetime',
        'last_shipment_date' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'kyc_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * Automatically hash password when setting
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Customer')
            ->logOnly([
                'company_name',
                'contact_person',
                'email',
                'status',
                'credit_limit',
                'customer_type',
                'account_manager_id',
            ])
            ->setDescriptionForEvent(fn (string $eventName) => "Customer {$eventName}");
    }

    // Relationships

    /**
     * Account manager (user) relationship
     */
    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    /**
     * Primary branch relationship
     */
    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    /**
     * Sales representative relationship
     */
    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    /**
     * Shipments relationship
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'customer_id');
    }

    /**
     * CRM Activities relationship
     */
    public function crmActivities(): HasMany
    {
        return $this->hasMany(\App\Models\CrmActivity::class, 'customer_id');
    }

    /**
     * CRM Reminders relationship
     */
    public function crmReminders(): HasMany
    {
        return $this->hasMany(\App\Models\CrmReminder::class, 'customer_id');
    }

    /**
     * Client Addresses relationship
     */
    public function clientAddresses(): HasMany
    {
        return $this->hasMany(\App\Models\ClientAddress::class, 'customer_id');
    }

    /**
     * Contracts relationship
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'customer_id');
    }

    /**
     * Quotations relationship
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'customer_id');
    }

    /**
     * Invoices relationship
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    /**
     * Address book relationship
     */
    public function addressBook(): HasMany
    {
        return $this->hasMany(AddressBook::class, 'customer_id');
    }

    /**
     * Primary address relationship
     */
    public function primaryAddress(): HasOne
    {
        return $this->hasOne(AddressBook::class, 'customer_id')->where('is_primary', true);
    }

    // Scopes

    /**
     * Active customers scope
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * VIP customers scope
     */
    public function scopeVip($query)
    {
        return $query->where('customer_type', 'vip');
    }

    /**
     * High priority customers scope
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority_level', 1);
    }

    /**
     * Customers with credit issues scope
     */
    public function scopeCreditIssues($query)
    {
        return $query->whereRaw('current_balance > credit_limit * 0.9');
    }

    /**
     * Recently active customers scope
     */
    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_shipment_date', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter customers by branch (for branch-level access)
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('primary_branch_id', $branchId);
    }

    /**
     * Scope to get customers visible to current user based on role
     */
    public function scopeVisibleToUser($query, $user = null)
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0'); // No access if not authenticated
        }

        // Super admin and regional managers see ALL customers
        if ($user->hasRole(['super-admin', 'super_admin', 'regional-manager', 'regional_manager', 'admin'])) {
            return $query; // No filtering
        }

        // Branch users see only their branch's customers
        $branchId = $user->primary_branch_id ?? $user->branchWorker?->branch_id ?? null;
        
        if ($branchId) {
            return $query->where('primary_branch_id', $branchId);
        }

        return $query->whereRaw('1 = 0'); // No access if no branch association
    }

    // Accessors & Mutators

    /**
     * Get customer's full display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->contact_person;
    }

    /**
     * Get customer's full address
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->billing_address ?: $this->shipping_address;
        $parts = array_filter([$address, $this->city, $this->state, $this->postal_code, $this->country]);

        return implode(', ', $parts);
    }

    /**
     * Get available credit
     */
    public function getAvailableCreditAttribute(): float
    {
        return max(0, $this->credit_limit - $this->current_balance);
    }

    /**
     * Check if customer is over credit limit
     */
    public function getIsOverCreditLimitAttribute(): bool
    {
        return $this->current_balance > $this->credit_limit;
    }

    /**
     * Get customer status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => '<span class="badge badge-success">Active</span>',
            'inactive' => '<span class="badge badge-secondary">Inactive</span>',
            'suspended' => '<span class="badge badge-warning">Suspended</span>',
            'blacklisted' => '<span class="badge badge-danger">Blacklisted</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Get customer type badge
     */
    public function getTypeBadgeAttribute(): string
    {
        return match($this->customer_type) {
            'vip' => '<span class="badge badge-primary">VIP</span>',
            'regular' => '<span class="badge badge-info">Regular</span>',
            'inactive' => '<span class="badge badge-secondary">Inactive</span>',
            'prospect' => '<span class="badge badge-warning">Prospect</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    // Business Logic Methods

    /**
     * Update customer statistics
     */
    public function updateStatistics(): void
    {
        $shipments = $this->shipments()->get();

        $this->update([
            'total_shipments' => $shipments->count(),
            'total_spent' => $shipments->sum('total_amount'),
            'average_order_value' => $shipments->avg('total_amount') ?: 0,
            'last_shipment_date' => $shipments->max('created_at'),
        ]);
    }

    /**
     * Check if customer can place order (credit check)
     */
    public function canPlaceOrder(float $orderAmount = 0): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->payment_terms === 'cod') {
            return true; // COD customers can always place orders
        }

        return ($this->current_balance + $orderAmount) <= $this->credit_limit;
    }

    /**
     * Get customer's risk level
     */
    public function getRiskLevel(): string
    {
        $riskScore = 0;

        // Credit utilization risk
        $utilizationRate = $this->credit_limit > 0 ? ($this->current_balance / $this->credit_limit) : 0;
        if ($utilizationRate > 0.9) $riskScore += 3;
        elseif ($utilizationRate > 0.7) $riskScore += 2;
        elseif ($utilizationRate > 0.5) $riskScore += 1;

        // Payment history risk (simplified)
        if ($this->complaints_count > 5) $riskScore += 2;
        elseif ($this->complaints_count > 2) $riskScore += 1;

        // Recency risk
        if ($this->last_shipment_date && $this->last_shipment_date->diffInDays(now()) > 90) {
            $riskScore += 1;
        }

        if ($riskScore >= 4) return 'high';
        if ($riskScore >= 2) return 'medium';
        return 'low';
    }

    /**
     * Get customer analytics summary
     */
    public function getAnalyticsSummary(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        $recentShipments = $this->shipments()->where('created_at', '>=', $startDate)->get();

        return [
            'period_days' => $days,
            'recent_shipments_count' => $recentShipments->count(),
            'recent_total_spent' => $recentShipments->sum('total_amount'),
            'average_order_value_recent' => $recentShipments->avg('total_amount') ?: 0,
            'last_shipment_date' => $this->last_shipment_date?->format('Y-m-d'),
            'days_since_last_shipment' => $this->last_shipment_date?->diffInDays(now()),
            'risk_level' => $this->getRiskLevel(),
            'credit_utilization' => $this->credit_limit > 0 ? ($this->current_balance / $this->credit_limit) * 100 : 0,
        ];
    }

    /**
     * Send communication to customer
     */
    public function sendCommunication(string $type, string $message, array $options = []): bool
    {
        $channels = $this->communication_channels ?? ['email'];

        foreach ($channels as $channel) {
            match($channel) {
                'email' => $this->sendEmail($message, $options),
                'sms' => $this->sendSms($message, $options),
                'whatsapp' => $this->sendWhatsapp($message, $options),
                default => null,
            };
        }

        $this->update(['last_contact_date' => now()]);

        return true;
    }

    /**
     * Placeholder methods for communication (to be implemented)
     */
    private function sendEmail(string $message, array $options = []): void
    {
        // Implementation for email sending
    }

    private function sendSms(string $message, array $options = []): void
    {
        // Implementation for SMS sending
    }

    private function sendWhatsapp(string $message, array $options = []): void
    {
        // Implementation for WhatsApp sending
    }

    /**
     * Generate unique customer code
     */
    public static function generateCustomerCode(): string
    {
        do {
            $code = 'CUST-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('customer_code', $code)->exists());

        return $code;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (!$customer->customer_code) {
                $customer->customer_code = self::generateCustomerCode();
            }

            if (!$customer->customer_since) {
                $customer->customer_since = now();
            }
        });
    }
}
