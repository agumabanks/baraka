<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum EmploymentStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case ON_LEAVE = 'ON_LEAVE';
    case TERMINATED = 'TERMINATED';
    case PROBATION = 'PROBATION';
    case SUSPENDED = 'SUSPENDED';

    public static function fromString(string $value): self
    {
        $normalized = Str::upper(str_replace([' ', '-'], '_', trim($value)));

        return self::tryFrom($normalized) ?? match ($normalized) {
            'ONLEAVE' => self::ON_LEAVE,
            'FIRED' => self::TERMINATED,
            'PENDING' => self::PROBATION,
            default => self::ACTIVE,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ON_LEAVE => 'On Leave',
            self::TERMINATED => 'Terminated',
            self::PROBATION => 'Probation',
            self::SUSPENDED => 'Suspended',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }
}
