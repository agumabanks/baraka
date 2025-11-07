<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Carbon\Carbon;

class ContractActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contract $contract,
        public ?int $activatedBy = null,
        public ?Carbon $activatedAt = null
    ) {
        $this->activatedAt = $activatedAt ?? now();
    }

    public function getEventData(): array
    {
        return [
            'contract' => $this->contract->getContractSummary(),
            'customer' => [
                'id' => $this->contract->customer->id,
                'name' => $this->contract->customer->company_name ?? $this->contract->customer->contact_person,
                'email' => $this->contract->customer->email
            ],
            'activated_by' => $this->activatedBy,
            'activated_at' => $this->activatedAt->toISOString(),
            'contract_duration_days' => $this->contract->start_date->diffInDays($this->contract->end_date),
            'service_level_count' => $this->contract->serviceLevelCommitments->count(),
            'volume_tier_count' => $this->contract->volumeDiscounts->count(),
            'compliance_requirement_count' => $this->contract->compliances->count()
        ];
    }
}