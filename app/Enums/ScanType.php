<?php

namespace App\Enums;

enum ScanType: string
{
    case BAG_IN = 'bag_in';
    case BAG_OUT = 'bag_out';
    case LOAD = 'load';
    case UNLOAD = 'unload';
    case ROUTE = 'route';
    case DELIVERY = 'delivery';
    case RETURN = 'return';
    case PICKUP = 'pickup';
    case TRANSFER = 'transfer';
    case SORT = 'sort';
    case DAMAGE = 'damage';
    case EXCEPTION = 'exception';

    public function label(): string
    {
        return match ($this) {
            self::BAG_IN => 'Bag In',
            self::BAG_OUT => 'Bag Out',
            self::LOAD => 'Load',
            self::UNLOAD => 'Unload',
            self::ROUTE => 'Route',
            self::DELIVERY => 'Delivery',
            self::RETURN => 'Return',
            self::PICKUP => 'Pickup',
            self::TRANSFER => 'Transfer',
            self::SORT => 'Sort',
            self::DAMAGE => 'Damage Report',
            self::EXCEPTION => 'Exception',
        };
    }

    public function requiresNote(): bool
    {
        return match ($this) {
            self::DAMAGE, self::EXCEPTION, self::RETURN => true,
            default => false,
        };
    }

    public function statusTransition(): ?ShipmentStatus
    {
        return match ($this) {
            self::PICKUP => ShipmentStatus::PICKED_UP,
            self::LOAD => ShipmentStatus::IN_TRANSIT,
            self::DELIVERY => ShipmentStatus::DELIVERED,
            self::RETURN => ShipmentStatus::RETURNED,
            self::EXCEPTION => ShipmentStatus::EXCEPTION,
            default => null,
        };
    }

    public static function getAllTypes(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
