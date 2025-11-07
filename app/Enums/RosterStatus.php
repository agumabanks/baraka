<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum RosterStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case NO_SHOW = 'NO_SHOW';
    case CANCELLED = 'CANCELLED';

    public static function fromString(string $value): self
    {
        $normalized = Str::upper(str_replace([' ', '-'], '_', trim($value)));

        return self::tryFrom($normalized) ?? match ($normalized) {
            'ONGOING' => self::IN_PROGRESS,
            'DONE' => self::COMPLETED,
            default => self::SCHEDULED,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::NO_SHOW => 'No Show',
            self::CANCELLED => 'Cancelled',
        };
    }
}
