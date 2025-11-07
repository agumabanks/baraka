<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case BOOKED = 'BOOKED';
    case PICKUP_SCHEDULED = 'PICKUP_SCHEDULED';
    case PICKED_UP = 'PICKED_UP';
    case AT_ORIGIN_HUB = 'AT_ORIGIN_HUB';
    case BAGGED = 'BAGGED';
    case LINEHAUL_DEPARTED = 'LINEHAUL_DEPARTED';
    case LINEHAUL_ARRIVED = 'LINEHAUL_ARRIVED';
    case AT_DESTINATION_HUB = 'AT_DESTINATION_HUB';
    case CUSTOMS_HOLD = 'CUSTOMS_HOLD';
    case CUSTOMS_CLEARED = 'CUSTOMS_CLEARED';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case RETURN_INITIATED = 'RETURN_INITIATED';
    case RETURN_IN_TRANSIT = 'RETURN_IN_TRANSIT';
    case RETURNED = 'RETURNED';
    case CANCELLED = 'CANCELLED';
    case EXCEPTION = 'EXCEPTION';

    public static function orderedLifecycle(): array
    {
        return [
            self::BOOKED,
            self::PICKUP_SCHEDULED,
            self::PICKED_UP,
            self::AT_ORIGIN_HUB,
            self::BAGGED,
            self::LINEHAUL_DEPARTED,
            self::LINEHAUL_ARRIVED,
            self::AT_DESTINATION_HUB,
            self::CUSTOMS_HOLD,
            self::CUSTOMS_CLEARED,
            self::OUT_FOR_DELIVERY,
            self::DELIVERED,
            self::RETURN_INITIATED,
            self::RETURN_IN_TRANSIT,
            self::RETURNED,
            self::CANCELLED,
            self::EXCEPTION,
        ];
    }

    public static function pickupStages(): array
    {
        return [self::BOOKED, self::PICKUP_SCHEDULED, self::PICKED_UP];
    }

    public static function transportStages(): array
    {
        return [self::AT_ORIGIN_HUB, self::BAGGED, self::LINEHAUL_DEPARTED, self::LINEHAUL_ARRIVED, self::AT_DESTINATION_HUB];
    }

    public static function deliveryStages(): array
    {
        return [self::CUSTOMS_HOLD, self::CUSTOMS_CLEARED, self::OUT_FOR_DELIVERY, self::DELIVERED];
    }

    public static function returnStages(): array
    {
        return [self::RETURN_INITIATED, self::RETURN_IN_TRANSIT, self::RETURNED];
    }

    public static function activeStatuses(): array
    {
        return array_values(array_filter(self::orderedLifecycle(), fn (self $status) => ! $status->isTerminal()));
    }

    public static function fromString(string $value): ?self
    {
        $normalized = strtoupper(trim($value));

        return self::tryFrom($normalized) ?? self::fromLegacy($normalized);
    }

    public static function fromLegacy(string $value): ?self
    {
        return match ($value) {
            'CREATED', 'CONFIRMED', 'READY_FOR_PICKUP', 'READY_FOR_ASSIGNMENT', 'PENDING', 'PENDING_PICKUP' => self::BOOKED,
            'ASSIGNED', 'ASSIGNED_TO_WORKER', 'SCHEDULED', 'PICKUP_SCHEDULED' => self::PICKUP_SCHEDULED,
            'HANDED_OVER', 'ARRIVE', 'AT_HUB', 'ARRIVAL_ORIGIN', 'AT_ORIGIN', 'ORIGIN_SORT' => self::AT_ORIGIN_HUB,
            'SORT', 'LOAD', 'BAGGED' => self::BAGGED,
            'DEPART', 'IN_TRANSIT', 'LINEHAUL', 'TRANSFER_TO_HUB', 'TRANSFER_TO_DESTINATION', 'IN_TRANSIT_TO_DESTINATION', 'IN_TRANSIT_TO_HUB' => self::LINEHAUL_DEPARTED,
            'ARRIVE_DEST', 'ARRIVED_DESTINATION', 'ARRIVED_AT_DESTINATION', 'LINEHAUL_ARRIVED' => self::LINEHAUL_ARRIVED,
            'DESTINATION_HUB', 'AT_DESTINATION', 'DESTINATION_SORT' => self::AT_DESTINATION_HUB,
            'RETURN_TO_SENDER' => self::RETURN_INITIATED,
            'DAMAGED' => self::EXCEPTION,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BOOKED => 'Booked',
            self::PICKUP_SCHEDULED => 'Pickup Scheduled',
            self::PICKED_UP => 'Picked Up',
            self::AT_ORIGIN_HUB => 'At Origin Hub',
            self::BAGGED => 'Bagged',
            self::LINEHAUL_DEPARTED => 'Linehaul Departed',
            self::LINEHAUL_ARRIVED => 'Linehaul Arrived',
            self::AT_DESTINATION_HUB => 'At Destination Hub',
            self::CUSTOMS_HOLD => 'In Customs Hold',
            self::CUSTOMS_CLEARED => 'Customs Cleared',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::RETURN_INITIATED => 'Return Initiated',
            self::RETURN_IN_TRANSIT => 'Return In Transit',
            self::RETURNED => 'Returned',
            self::CANCELLED => 'Cancelled',
            self::EXCEPTION => 'Exception',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::RETURNED,
            self::CANCELLED,
            self::EXCEPTION,
        ], true);
    }

    public function associatedTimestampColumn(): ?string
    {
        return match ($this) {
            self::BOOKED => 'booked_at',
            self::PICKUP_SCHEDULED => 'pickup_scheduled_at',
            self::PICKED_UP => 'picked_up_at',
            self::AT_ORIGIN_HUB => 'origin_hub_arrived_at',
            self::BAGGED => 'bagged_at',
            self::LINEHAUL_DEPARTED => 'linehaul_departed_at',
            self::LINEHAUL_ARRIVED => 'linehaul_arrived_at',
            self::AT_DESTINATION_HUB => 'destination_hub_arrived_at',
            self::CUSTOMS_HOLD => 'customs_hold_at',
            self::CUSTOMS_CLEARED => 'customs_cleared_at',
            self::OUT_FOR_DELIVERY => 'out_for_delivery_at',
            self::DELIVERED => 'delivered_at',
            self::RETURN_INITIATED => 'return_initiated_at',
            self::RETURN_IN_TRANSIT => 'return_in_transit_at',
            self::RETURNED => 'returned_at',
            self::CANCELLED => 'cancelled_at',
            self::EXCEPTION => 'exception_occurred_at',
        };
    }
}
