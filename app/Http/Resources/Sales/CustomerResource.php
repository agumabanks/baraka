<?php

namespace App\Http\Resources\Sales;

use App\Enums\Status as UserStatus;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    private const DORMANT_THRESHOLD_DAYS = 60;

    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $shipmentsThisMonth = (int) ($this->shipments_this_month ?? 0);
        $shipmentsTotal = (int) ($this->shipments_total ?? $this->shipments_count ?? 0);
        $lastShipmentAt = $this->parseDate($this->last_shipment_at ?? null);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->mobile ?? $this->phone_e164,
            'address' => $this->address,
            'hub' => $this->whenLoaded('hub', function () {
                return $this->hub ? [
                    'id' => $this->hub->id,
                    'name' => $this->hub->name,
                ] : null;
            }, null),
            'shipments' => [
                'this_month' => $shipmentsThisMonth,
                'total' => $shipmentsTotal,
            ],
            'status' => [
                'account' => ((int) $this->status) === UserStatus::ACTIVE ? 'active' : 'inactive',
                'engagement' => $this->resolveEngagementStatus($shipmentsThisMonth, $lastShipmentAt),
            ],
            'last_shipment_at' => $lastShipmentAt?->toIso8601String(),
            'last_activity_human' => $lastShipmentAt?->diffForHumans(null, Carbon::DIFF_RELATIVE_TO_NOW),
            'created_at' => $this->parseDate($this->created_at)?->toIso8601String(),
            'updated_at' => $this->parseDate($this->updated_at)?->toIso8601String(),
        ];
    }

    private function resolveEngagementStatus(int $shipmentsThisMonth, ?Carbon $lastShipmentAt): string
    {
        if ($shipmentsThisMonth > 0) {
            return 'active';
        }

        if ($lastShipmentAt && $lastShipmentAt->greaterThanOrEqualTo(Carbon::now()->subDays(self::DORMANT_THRESHOLD_DAYS))) {
            return 'at_risk';
        }

        return 'dormant';
    }

    private function parseDate($value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }
}

