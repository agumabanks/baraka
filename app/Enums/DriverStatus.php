<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum DriverStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case ON_LEAVE = 'ON_LEAVE';
    case OFFBOARDING = 'OFFBOARDING';

    public static function fromString(string $value): self
    {
        $normalized = Str::upper(str_replace([' ', '-'], '_', trim($value)));

        return self::tryFrom($normalized) ?? match ($normalized) {
            'LEAVE' => self::ON_LEAVE,
            'PAUSED' => self::SUSPENDED,
            'OFFBOARDED' => self::INACTIVE,
            default => self::ACTIVE,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::ON_LEAVE => 'On Leave',
            self::OFFBOARDING => 'Offboarding',
        };
    }
}
