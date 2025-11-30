<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING = 'PENDING';
    case SENT = 'SENT';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';

    public static function fromString(string $value): ?self
    {
        $normalized = strtoupper(trim($value));
        
        return self::tryFrom($normalized) ?? self::fromLegacy($normalized);
    }

    public static function fromLegacy(string $value): ?self
    {
        // Handle numeric statuses from legacy code
        return match ($value) {
            '1' => self::DRAFT,
            '2' => self::PENDING,
            '3' => self::PAID,
            '4' => self::OVERDUE,
            '5' => self::CANCELLED,
            'FINALIZED' => self::PENDING,
            'PARTIALLY_PAID' => self::PENDING,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::SENT => 'Sent',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING => 'warning',
            self::SENT => 'info',
            self::PAID => 'success',
            self::OVERDUE => 'danger',
            self::CANCELLED => 'dark',
            self::REFUNDED => 'primary',
        };
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::PENDING, self::SENT, self::OVERDUE], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED, self::REFUNDED], true);
    }

    public static function activeStatuses(): array
    {
        return [self::PENDING, self::SENT, self::OVERDUE];
    }

    public static function allStatuses(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
