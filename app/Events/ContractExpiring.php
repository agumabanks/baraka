<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Carbon\Carbon;

class ContractExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contract $contract,
        public int $daysUntilExpiry,
        public ?Carbon $expiryDate = null,
        public ?string $notificationType = null
    ) {
        $this->expiryDate = $expiryDate ?? $contract->end_date;
    }

    public function getEventData(): array
    {
        return [
            'contract' => $this->contract->getContractSummary(),
            'customer' => [
                'id' => $this->contract->customer->id,
                'name' => $this->contract->customer->company_name ?? $this->contract->customer->contact_person,
                'email' => $this->contract->customer->email,
                'phone' => $this->contract->customer->phone
            ],
            'expiry_details' => [
                'days_until_expiry' => $this->daysUntilExpiry,
                'expiry_date' => $this->expiryDate->toISOString(),
                'is_expiring_soon' => $this->daysUntilExpiry <= 7,
                'is_critical_expiry' => $this->daysUntilExpiry <= 3
            ],
            'auto_renewal' => [
                'enabled' => !is_null($this->contract->auto_renewal_terms),
                'terms' => $this->contract->auto_renewal_terms,
                'will_auto_renew' => ($this->contract->auto_renewal_terms['auto_renewal'] ?? false) && 
                                   $this->daysUntilExpiry > ($this->contract->auto_renewal_terms['notice_period_days'] ?? 30)
            ],
            'contract_performance' => [
                'current_volume' => $this->contract->current_volume ?? 0,
                'volume_commitment' => $this->contract->volume_commitment,
                'compliance_score' => $this->contract->getComplianceStatus()['score'] ?? null
            ],
            'notification_type' => $this->notificationType
        ];
    }
}