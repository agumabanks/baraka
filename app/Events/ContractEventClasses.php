<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Carbon\Carbon;

class ContractExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contract $contract,
        public ?Contract $renewalContract = null,
        public ?string $expiredBy = null
    ) {}

    public function getEventData(): array
    {
        return [
            'expired_contract' => $this->contract->getContractSummary(),
            'customer' => [
                'id' => $this->contract->customer->id,
                'name' => $this->contract->customer->company_name ?? $this->contract->customer->contact_person,
                'email' => $this->contract->customer->email
            ],
            'renewal_contract' => $this->renewalContract?->getContractSummary(),
            'expired_by' => $this->expiredBy,
            'contract_duration' => $this->contract->start_date->diffInDays($this->contract->end_date),
            'final_performance' => [
                'final_volume' => $this->contract->current_volume ?? 0,
                'volume_commitment_met' => ($this->contract->current_volume ?? 0) >= ($this->contract->volume_commitment ?? 0),
                'compliance_score' => $this->contract->getComplianceStatus()['score'] ?? null
            ]
        ];
    }
}

class ContractRenewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contract $newContract,
        public Contract $originalContract,
        public array $renewalDetails = []
    ) {}

    public function getEventData(): array
    {
        return [
            'new_contract' => $this->newContract->getContractSummary(),
            'original_contract' => $this->originalContract->getContractSummary(),
            'renewal_details' => [
                'renewal_count' => $this->newContract->renewal_count,
                'previous_volume' => $this->originalContract->current_volume ?? 0,
                'renewal_terms_changed' => !empty($this->renewalDetails),
                'terms_modifications' => $this->renewalDetails
            ],
            'performance_summary' => [
                'previous_duration' => $this->originalContract->start_date->diffInDays($this->originalContract->end_date),
                'volume_progression' => [
                    'starting_volume' => $this->originalContract->current_volume ?? 0,
                    'final_volume' => $this->newContract->current_volume ?? 0
                ]
            ]
        ];
    }
}

class ContractComplianceBreached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public \App\Models\ContractCompliance $compliance,
        public array $breachDetails = []
    ) {}

    public function getEventData(): array
    {
        return [
            'contract' => [
                'id' => $this->compliance->contract->id,
                'name' => $this->compliance->contract->name
            ],
            'compliance_requirement' => [
                'id' => $this->compliance->id,
                'name' => $this->compliance->requirement_name,
                'type' => $this->compliance->compliance_type,
                'is_critical' => $this->compliance->is_critical
            ],
            'breach_details' => [
                'current_performance' => $this->compliance->performance_percentage,
                'target_value' => $this->compliance->target_value,
                'actual_value' => $this->compliance->actual_value,
                'consecutive_breaches' => $this->compliance->consecutive_breaches,
                'breach_severity' => $this->compliance->getRiskLevel()
            ],
            'resolution_required' => [
                'deadline' => $this->compliance->resolution_deadline?->toISOString(),
                'required_actions' => $this->compliance->getRequiredActions(),
                'escalation_level' => $this->compliance->escalation_level
            ]
        ];
    }
}

class ContractComplianceEscalated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public \App\Models\ContractCompliance $compliance,
        public int $escalationLevel
    ) {}

    public function getEventData(): array
    {
        return [
            'contract_id' => $this->compliance->contract->id,
            'requirement_name' => $this->compliance->requirement_name,
            'escalation_level' => $this->escalationLevel,
            'escalation_reason' => $this->getEscalationReason(),
            'escalated_at' => now()->toISOString()
        ];
    }

    private function getEscalationReason(): string
    {
        if ($this->escalationLevel === 1) {
            return 'Compliance breach requiring management attention';
        } elseif ($this->escalationLevel === 2) {
            return 'Repeated compliance breaches requiring senior management intervention';
        } else {
            return 'Critical compliance failure requiring executive attention';
        }
    }
}

class ContractComplianceResolved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public \App\Models\ContractCompliance $compliance) {}

    public function getEventData(): array
    {
        return [
            'contract_id' => $this->compliance->contract->id,
            'requirement_name' => $this->compliance->requirement_name,
            'resolution_details' => [
                'resolved_performance' => $this->compliance->performance_percentage,
                'target_met' => $this->compliance->performance_percentage >= $this->compliance->target_value,
                'breach_duration' => $this->getBreachDuration(),
                'resolution_time_hours' => $this->getResolutionTime()
            ]
        ];
    }

    private function getBreachDuration(): int
    {
        // Calculate how long the breach lasted
        return $this->compliance->last_breach_at?->diffInHours(now()) ?? 0;
    }

    private function getResolutionTime(): int
    {
        // Calculate resolution time from breach to resolution
        return $this->compliance->last_breach_at?->diffInHours(now()) ?? 0;
    }
}

class ContractVolumeTierAchieved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contract $contract,
        public \App\Models\ContractVolumeDiscount $tier
    ) {}

    public function getEventData(): array
    {
        return [
            'contract' => $this->contract->getContractSummary(),
            'tier_achieved' => [
                'tier_id' => $this->tier->id,
                'tier_name' => $this->tier->tier_name,
                'volume_requirement' => $this->tier->volume_requirement,
                'discount_percentage' => $this->tier->discount_percentage,
                'benefits' => $this->tier->benefits
            ],
            'current_progress' => [
                'current_volume' => $this->contract->current_volume ?? 0,
                'achievement_time' => now()->toISOString()
            ],
            'impact' => [
                'immediate_benefits' => $this->getImmediateBenefits(),
                'projected_savings' => $this->getProjectedSavings()
            ]
        ];
    }

    private function getImmediateBenefits(): array
    {
        return $this->tier->benefits ?? [];
    }

    private function getProjectedSavings(): float
    {
        // Calculate projected savings based on current volume and discount
        $monthlyVolume = ($this->contract->current_volume ?? 0) / 12; // Estimate monthly
        $averageRate = 5.0; // Placeholder average rate per unit
        return ($monthlyVolume * $averageRate) * ($this->tier->discount_percentage / 100);
    }
}

class ContractVolumeMilestoneReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contract $contract,
        public \App\Models\CustomerMilestone $milestone
    ) {}

    public function getEventData(): array
    {
        return [
            'contract_id' => $this->contract->id,
            'customer' => [
                'id' => $this->contract->customer->id,
                'name' => $this->contract->customer->company_name ?? $this->contract->customer->contact_person
            ],
            'milestone' => [
                'type' => $this->milestone->milestone_type,
                'value' => $this->milestone->milestone_value,
                'title' => $this->milestone->getMilestoneTitle(),
                'achieved_at' => $this->milestone->achieved_at->toISOString()
            ],
            'reward' => [
                'given' => $this->milestone->reward_given,
                'details' => $this->milestone->reward_details
            ]
        ];
    }
}

class ContractMilestoneAchieved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public \App\Models\CustomerMilestone $milestone) {}

    public function getEventData(): array
    {
        return [
            'customer' => [
                'id' => $this->milestone->customer->id,
                'name' => $this->milestone->customer->company_name ?? $this->milestone->customer->contact_person
            ],
            'milestone' => [
                'type' => $this->milestone->milestone_type,
                'value' => $this->milestone->milestone_value,
                'title' => $this->milestone->getMilestoneTitle(),
                'achieved_at' => $this->milestone->achieved_at->toISOString()
            ],
            'customer_lifetime_value' => $this->calculateCustomerLTV()
        ];
    }

    private function calculateCustomerLTV(): float
    {
        // Calculate customer lifetime value based on milestone type and value
        return match($this->milestone->milestone_type) {
            'shipment_count' => $this->milestone->milestone_value * 15.0, // Average $15 per shipment
            'revenue_volume' => $this->milestone->milestone_value,
            'tenure' => $this->milestone->milestone_value * 100, // $100 per month of tenure
            default => 0
        };
    }
}