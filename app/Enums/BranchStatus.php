<?php

namespace App\Enums;

enum BranchStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case MAINTENANCE = 'MAINTENANCE';
    case SUSPENDED = 'SUSPENDED';

    /**
     * Convert legacy integer based status values into the new enum.
     */
    public static function fromLegacy(?int $legacy): self
    {
        return match ($legacy) {
            1 => self::ACTIVE,
            0 => self::INACTIVE,
            2 => self::MAINTENANCE,
            default => self::ACTIVE,
        };
    }

    /**
     * Convert arbitrary string to BranchStatus.
     */
    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        return self::tryFrom($normalized) ?? match ($normalized) {
            'MAINTAINENCE', 'MAINTAINANCE' => self::MAINTENANCE,
            'PAUSED' => self::SUSPENDED,
            'ON' => self::ACTIVE,
            'OFF' => self::INACTIVE,
            default => self::ACTIVE,
        };
    }

    /**
     * Map enum back to integer status for backward compatibility.
     */
    public function toLegacy(): int
    {
        return match ($this) {
            self::ACTIVE => 1,
            self::INACTIVE => 0,
            self::MAINTENANCE => 2,
            self::SUSPENDED => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::MAINTENANCE => 'Maintenance',
            self::SUSPENDED => 'Suspended',
        };
    }
}
