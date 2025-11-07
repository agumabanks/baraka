<?php

namespace App\Enums;

enum ScanType: string
{
    case BOOKING_CONFIRMED = 'BOOKING_CONFIRMED';
    case PICKUP_CONFIRMED = 'PICKUP_CONFIRMED';
    case PICKUP_COMPLETED = 'PICKUP_COMPLETED';
    case ORIGIN_ARRIVAL = 'ORIGIN_ARRIVAL';
    case BAGGED = 'BAGGED';
    case LINEHAUL_DEPARTED = 'LINEHAUL_DEPARTED';
    case LINEHAUL_ARRIVED = 'LINEHAUL_ARRIVED';
    case DESTINATION_ARRIVAL = 'DESTINATION_ARRIVAL';
    case CUSTOMS_HOLD = 'CUSTOMS_HOLD';
    case CUSTOMS_CLEARED = 'CUSTOMS_CLEARED';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERY_CONFIRMED = 'DELIVERY_CONFIRMED';
    case RETURN_INITIATED = 'RETURN_INITIATED';
    case RETURN_RECEIVED = 'RETURN_RECEIVED';
    case RETURN_COMPLETED = 'RETURN_COMPLETED';
    case EXCEPTION = 'EXCEPTION';

    public static function fromString(string $value): ?self
    {
        $normalized = strtoupper(trim($value));

        return self::tryFrom($normalized) ?? self::fromLegacy($normalized);
    }

    private static function fromLegacy(string $value): ?self
    {
        return match ($value) {
            'ARRIVE' => self::ORIGIN_ARRIVAL,
            'SORT' => self::BAGGED,
            'LOAD' => self::BAGGED,
            'DEPART' => self::LINEHAUL_DEPARTED,
            'IN_TRANSIT' => self::LINEHAUL_DEPARTED,
            'ARRIVE_DEST' => self::DESTINATION_ARRIVAL,
            'DELIVERED' => self::DELIVERY_CONFIRMED,
            'RETURN_TO_SENDER' => self::RETURN_INITIATED,
            'DAMAGED' => self::EXCEPTION,
            default => null,
        };
    }

    public function resultingStatus(): ?ShipmentStatus
    {
        return match ($this) {
            self::BOOKING_CONFIRMED => ShipmentStatus::BOOKED,
            self::PICKUP_CONFIRMED => ShipmentStatus::PICKUP_SCHEDULED,
            self::PICKUP_COMPLETED => ShipmentStatus::PICKED_UP,
            self::ORIGIN_ARRIVAL => ShipmentStatus::AT_ORIGIN_HUB,
            self::BAGGED => ShipmentStatus::BAGGED,
            self::LINEHAUL_DEPARTED => ShipmentStatus::LINEHAUL_DEPARTED,
            self::LINEHAUL_ARRIVED => ShipmentStatus::LINEHAUL_ARRIVED,
            self::DESTINATION_ARRIVAL => ShipmentStatus::AT_DESTINATION_HUB,
            self::CUSTOMS_HOLD => ShipmentStatus::CUSTOMS_HOLD,
            self::CUSTOMS_CLEARED => ShipmentStatus::CUSTOMS_CLEARED,
            self::OUT_FOR_DELIVERY => ShipmentStatus::OUT_FOR_DELIVERY,
            self::DELIVERY_CONFIRMED => ShipmentStatus::DELIVERED,
            self::RETURN_INITIATED => ShipmentStatus::RETURN_INITIATED,
            self::RETURN_RECEIVED => ShipmentStatus::RETURN_IN_TRANSIT,
            self::RETURN_COMPLETED => ShipmentStatus::RETURNED,
            self::EXCEPTION => ShipmentStatus::EXCEPTION,
        };
    }
}
