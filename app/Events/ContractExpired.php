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