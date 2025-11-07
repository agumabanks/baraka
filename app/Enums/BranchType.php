<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum BranchType: string
{
    case HUB = 'HUB';
    case REGIONAL_BRANCH = 'REGIONAL_BRANCH';
    case DESTINATION_BRANCH = 'DESTINATION_BRANCH';
    case AGENT_POINT = 'AGENT_POINT';
    case MICRO_DEPOT = 'MICRO_DEPOT';
    case FULFILLMENT_CENTER = 'FULFILLMENT_CENTER';

    /**
     * Normalize arbitrary input (legacy values, lowercase strings, etc.) into a BranchType enum instance.
     */
    public static function fromString(string $value): self
    {
        $normalized = Str::upper(trim($value));

        return match ($normalized) {
            'REGIONAL', 'REGION', 'REGIONAL_DEPOT' => self::REGIONAL_BRANCH,
            'LOCAL', 'DESTINATION', 'DEST_BRANCH', 'DESTINATION_DEPOT' => self::DESTINATION_BRANCH,
            'AGENT', 'AGENCY', 'AGENT_DEPOT' => self::AGENT_POINT,
            'MICRODEPOT', 'MICRO_DEPOT' => self::MICRO_DEPOT,
            'FULFILLMENT', 'FULFILLMENT_CENTRE', 'FULFILMENT_CENTER' => self::FULFILLMENT_CENTER,
            default => self::tryFrom($normalized) ?? self::DESTINATION_BRANCH,
        };
    }

    /**
     * Display label used in UI responses.
     */
    public function label(): string
    {
        return match ($this) {
            self::HUB => 'Hub',
            self::REGIONAL_BRANCH => 'Regional Branch',
            self::DESTINATION_BRANCH => 'Destination Branch',
            self::AGENT_POINT => 'Agent Point',
            self::MICRO_DEPOT => 'Micro Depot',
            self::FULFILLMENT_CENTER => 'Fulfillment Center',
        };
    }

    /**
     * Options array for form select inputs.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }
}
