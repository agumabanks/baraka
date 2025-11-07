<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class Contract extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'customer_id', 'name', 'start_date', 'end_date', 'rate_card_id', 'sla_json', 'status', 'notes',
        'template_id', 'contract_type', 'volume_commitment', 'volume_commitment_period', 'current_volume',
        'discount_tiers', 'service_level_commitments', 'auto_renewal_terms', 'compliance_requirements',
        'notification_settings'
    ];

    protected $casts = [
        'sla_json' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'template_id' => 'integer',
        'contract_type' => 'string',
        'volume_commitment' => 'integer',
        'volume_commitment_period' => 'string',
        'current_volume' => 'integer',
        'discount_tiers' => 'array',
        'service_level_commitments' => 'array',
        'auto_renewal_terms' => 'array',
        'compliance_requirements' => 'array',
        'notification_settings' => 'array',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->useLogName('Contract')
            ->logOnly(['name', 'status', 'start_date', 'end_date', 'contract_type', 'volume_commitment'])
            ->logOnlyDirty();
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function rateCard()
    {
        return $this->belongsTo(RateCard::class);
    }

    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    public function serviceLevelCommitments()
    {
        return $this->hasMany(ContractServiceLevel::class);
    }

    public function volumeDiscounts()
    {
        return $this->hasMany(ContractVolumeDiscount::class);
    }

    public function notifications()
    {
        return $this->hasMany(ContractNotification::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('end_date', '<=', now()->addDays($days))
                    ->where('end_date', '>=', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('contract_type', $type);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Business Logic
    public function getStatusBadge(): string
    {
        return match($this->status) {
            'active' => '<span class="badge badge-success">Active</span>',
            'suspended' => '<span class="badge badge-warning">Suspended</span>',
            'expired' => '<span class="badge badge-danger">Expired</span>',
            'ended' => '<span class="badge badge-secondary">Ended</span>',
            'draft' => '<span class="badge badge-light">Draft</span>',
            'negotiation' => '<span class="badge badge-info">Negotiation</span>',
            default => '<span class="badge badge-light">Unknown</span>'
        };
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now()->toDateString() && 
               $this->end_date >= now()->toDateString();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->end_date->isBefore(now()->addDays($days)->toDateString()) &&
               $this->end_date->isAfter(now()->toDateString());
    }

    public function isExpired(): bool
    {
        return $this->end_date < now()->toDateString();
    }

    public function getDaysUntilExpiry(): ?int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return now()->diffInDays($this->end_date, false);
    }

    public function getVolumeProgress(): array
    {
        if (!$this->volume_commitment) {
            return [
                'has_commitment' => false,
                'progress_percentage' => 0,
                'current_volume' => 0,
                'required_volume' => 0,
                'remaining_volume' => 0
            ];
        }
        
        $progressPercentage = min(100, ($this->current_volume / $this->volume_commitment) * 100);
        $remainingVolume = max(0, $this->volume_commitment - $this->current_volume);
        
        return [
            'has_commitment' => true,
            'progress_percentage' => $progressPercentage,
            'current_volume' => $this->current_volume,
            'required_volume' => $this->volume_commitment,
            'remaining_volume' => $remainingVolume,
            'is_achieved' => $this->current_volume >= $this->volume_commitment
        ];
    }

    public function getApplicableDiscount(float $orderVolume): ?array
    {
        if (!$this->discount_tiers) {
            return null;
        }
        
        $applicableDiscount = null;
        $highestThreshold = 0;
        
        foreach ($this->discount_tiers as $tier) {
            $threshold = $tier['volume_requirement'] ?? 0;
            
            if ($orderVolume >= $threshold && $threshold > $highestThreshold) {
                $applicableDiscount = $tier;
                $highestThreshold = $threshold;
            }
        }
        
        return $applicableDiscount;
    }

    public function getServiceLevelCommitments(): array
    {
        return $this->service_level_commitments ?? [];
    }

    public function getComplianceStatus(): array
    {
        $requirements = $this->compliance_requirements ?? [];
        $status = [];
        
        foreach ($requirements as $requirement) {
            $status[$requirement] = [
                'required' => true,
                'met' => true, // This would be implemented with actual compliance checking
                'last_checked' => now()->toDateString()
            ];
        }
        
        return $status;
    }

    public function getContractSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'customer' => $this->customer->company_name ?? $this->customer->contact_person,
            'type' => $this->contract_type,
            'status' => $this->status,
            'status_badge' => $this->getStatusBadge(),
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'duration_days' => $this->start_date->diffInDays($this->end_date),
            'days_until_expiry' => $this->getDaysUntilExpiry(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'volume_progress' => $this->getVolumeProgress(),
            'has_template' => !is_null($this->template_id),
            'template_name' => $this->template?->name,
        ];
    }

    public function updateVolume(int $volume): void
    {
        $this->increment('current_volume', $volume);
        
        // Check if volume commitment is met
        if ($this->volume_commitment && $this->current_volume >= $this->volume_commitment) {
            // Trigger volume commitment reached event
            event(new \App\Events\ContractVolumeCommitmentReached($this));
        }
    }

    public function renewContract(Carbon $newEndDate): bool
    {
        // Validate renewal terms
        $renewalTerms = $this->auto_renewal_terms;
        
        if (!$renewalTerms || !($renewalTerms['auto_renewal'] ?? false)) {
            return false;
        }
        
        $noticePeriod = $renewalTerms['notice_period_days'] ?? 30;
        $maxExtensions = $renewalTerms['max_extensions'] ?? 1;
        $extensionDuration = $renewalTerms['extension_duration_days'] ?? 365;
        
        // Check if contract can be renewed
        $extensionCount = $this->where('original_contract_id', $this->id)->count();
        
        if ($extensionCount >= $maxExtensions) {
            return false;
        }
        
        // Update contract end date
        $this->update([
            'end_date' => $newEndDate,
            'notes' => ($this->notes ?? '') . "\nAuto-renewed on " . now()->toDateString()
        ]);
        
        return true;
    }

    public function createRenewal(): ?self
    {
        $renewalTerms = $this->auto_renewal_terms;
        
        if (!$renewalTerms || !($renewalTerms['auto_renewal'] ?? false)) {
            return null;
        }
        
        $extensionDuration = $renewalTerms['extension_duration_days'] ?? 365;
        $newEndDate = $this->end_date->copy()->addDays($extensionDuration);
        
        $renewal = $this->replicate();
        $renewal->name = $this->name . ' (Renewal)';
        $renewal->start_date = $this->end_date->copy()->addDay();
        $renewal->end_date = $newEndDate;
        $renewal->status = 'active';
        $renewal->current_volume = 0; // Reset volume for new contract
        $renewal->original_contract_id = $this->id;
        
        $renewal->save();
        
        return $renewal;
    }
}