<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum DriverTimeLogType: string
{
    case CHECK_IN = 'CHECK_IN';
    case CHECK_OUT = 'CHECK_OUT';
    case BREAK_START = 'BREAK_START';
    case BREAK_END = 'BREAK_END';
    case PAUSE = 'PAUSE';

    public static function fromString(string $value): self
    {
        $normalized = Str::upper(str_replace([' ', '-'], '_', trim($value)));

        return self::tryFrom($normalized) ?? match ($normalized) {
            'START' => self::CHECK_IN,
            'END' => self::CHECK_OUT,
            default => self::CHECK_IN,
        };
    }
}
