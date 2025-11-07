<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class CustomerMilestone extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id',
        'milestone_type',
        'milestone_value',
        'achieved_at',
        'reward_given',
        'reward_details'
    ];

    protected $casts = [
        'milestone_value' => 'integer',
        'achieved_at' => 'datetime',
        'reward_details' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('CustomerMilestone')
            ->logOnly(['milestone_type', 'milestone_value', 'achieved_at'])
            ->setDescriptionForEvent(fn (string $eventName) => "Customer milestone {$eventName}");
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('milestone_type', $type);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('achieved_at', '>=', now()->subDays($days));
    }

    // Business Logic
    public function getMilestoneTitle(): string
    {
        return match($this->milestone_type) {
            'shipment_count' => "{$this->milestone_value} Shipments",
            'revenue_volume' => '$' . number_format($this->milestone_value) . ' Revenue',
            'tenure' => $this->getTenureText(),
            'tier_upgrade' => "Upgraded to {$this->milestone_value}",
            default => "Milestone: {$this->milestone_value}"
        };
    }

    public function getTenureText(): string
    {
        $years = floor($this->milestone_value / 12);
        $months = $this->milestone_value % 12;
        
        if ($years > 0) {
            return $months > 0 ? "{$years} years {$months} months" : "{$years} years";
        }
        
        return "{$months} months";
    }

    public static function checkAndCreateMilestone(Customer $customer, string $type, int $value): ?self
    {
        // Check if milestone already exists
        $existing = self::where('customer_id', $customer->id)
            ->where('milestone_type', $type)
            ->where('milestone_value', $value)
            ->first();

        if ($existing) {
            return null;
        }

        // Create the milestone
        $milestone = self::create([
            'customer_id' => $customer->id,
            'milestone_type' => $type,
            'milestone_value' => $value,
            'achieved_at' => now(),
            'reward_given' => self::getDefaultReward($type, $value),
            'reward_details' => self::getRewardDetails($type, $value)
        ]);

        return $milestone;
    }

    private static function getDefaultReward(string $type, int $value): ?string
    {
        return match($type) {
            'shipment_count' => match($value) {
                10 => '5% discount on next shipment',
                50 => 'Free premium insurance',
                100 => 'Dedicated account manager',
                500 => 'Platinum tier upgrade',
                default => null
            },
            'revenue_volume' => match($value) {
                1000 => 'Bronze tier consideration',
                5000 => 'Silver tier upgrade',
                10000 => 'Gold tier benefits',
                default => null
            },
            'tenure' => match($value) {
                6 => 'Loyalty points bonus',
                12 => '1 month free service',
                24 => 'Preferred customer status',
                default => null
            },
            default => null
        };
    }

    private static function getRewardDetails(string $type, int $value): array
    {
        return match($type) {
            'shipment_count' => [
                'type' => 'milestone_reward',
                'value' => $value,
                'reward_type' => 'discount_or_benefit',
                'description' => 'Achieved shipping milestone'
            ],
            'revenue_volume' => [
                'type' => 'revenue_milestone',
                'value' => $value,
                'currency' => 'USD',
                'description' => 'Achieved revenue milestone'
            ],
            'tenure' => [
                'type' => 'loyalty_milestone',
                'value' => $value,
                'unit' => 'months',
                'description' => 'Customer loyalty milestone'
            ],
            default => ['type' => 'custom_milestone', 'value' => $value]
        };
    }
}