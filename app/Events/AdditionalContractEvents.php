<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

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
                'renewal_count' => $this->newContract->renewal_count ?? 1,
                'previous_volume' => $this->originalContract->current_volume ?? 0
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
            'escalated_at' => now()->toISOString()
        ];
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
            'resolved_at' => now()->toISOString()
        ];
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
                'tier_name' => $this->tier->tier_name,
                'volume_requirement' => $this->tier->volume_requirement,
                'discount_percentage' => $this->tier->discount_percentage
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
                'title' => $this->milestone->getMilestoneTitle()
            ]
        ];
    }
}