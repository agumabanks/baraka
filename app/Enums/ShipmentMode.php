<?php

namespace App\Enums;

enum ShipmentMode: string
{
    case INDIVIDUAL = 'individual';
    case GROUPAGE = 'groupage';

    /**
     * Resolve a shipment mode from an arbitrary input.
     */
    public static function fromString(?string $value): self
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'group', 'grouped', 'groupage' => self::GROUPAGE,
            default => self::INDIVIDUAL,
        };
    }

    /**
     * Human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::GROUPAGE => 'Groupage',
            self::INDIVIDUAL => 'Individual',
        };
    }

    /**
     * Option map for dropdowns.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mode) => [$mode->value => $mode->label()])
            ->all();
    }
}
