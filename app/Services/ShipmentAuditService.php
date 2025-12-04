<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentAudit;

class ShipmentAuditService
{
    public function logCreated(Shipment $shipment): ShipmentAudit
    {
        return ShipmentAudit::log(
            $shipment->id,
            ShipmentAudit::EVENT_CREATED,
            null,
            $shipment->toArray()
        );
    }

    public function logUpdated(Shipment $shipment, array $oldValues, array $newValues, ?string $reason = null): ShipmentAudit
    {
        return ShipmentAudit::log(
            $shipment->id,
            ShipmentAudit::EVENT_UPDATED,
            $oldValues,
            $newValues,
            $reason
        );
    }

    public function logStatusChanged(Shipment $shipment, string $oldStatus, string $newStatus, ?string $reason = null): ShipmentAudit
    {
        return ShipmentAudit::log(
            $shipment->id,
            ShipmentAudit::EVENT_STATUS_CHANGED,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $reason
        );
    }

    public function logDiscountApplied(Shipment $shipment, float $discountAmount, string $reason, ?int $approvedBy = null): ShipmentAudit
    {
        return ShipmentAudit::log(
            $shipment->id,
            ShipmentAudit::EVENT_DISCOUNT_APPLIED,
            ['price_amount' => $shipment->getOriginal('price_amount')],
            [
                'price_amount' => $shipment->price_amount,
                'discount_amount' => $discountAmount,
                'approved_by' => $approvedBy,
            ],
            $reason
        );
    }

    public function logLabelPrinted(Shipment $shipment, bool $isReprint = false): ShipmentAudit
    {
        $eventType = $isReprint ? ShipmentAudit::EVENT_LABEL_REPRINTED : ShipmentAudit::EVENT_LABEL_PRINTED;

        return ShipmentAudit::log(
            $shipment->id,
            $eventType,
            ['label_print_count' => $shipment->getOriginal('label_print_count') ?? 0],
            ['label_print_count' => $shipment->label_print_count]
        );
    }

    public function logPaymentReceived(Shipment $shipment, float $amount, string $method): ShipmentAudit
    {
        return ShipmentAudit::log(
            $shipment->id,
            ShipmentAudit::EVENT_PAYMENT_RECEIVED,
            ['payment_status' => $shipment->getOriginal('payment_status')],
            [
                'payment_status' => $shipment->payment_status,
                'amount' => $amount,
                'method' => $method,
            ]
        );
    }

    public function logCancelled(Shipment $shipment, string $reason): ShipmentAudit
    {
        return ShipmentAudit::log(
            $shipment->id,
            ShipmentAudit::EVENT_CANCELLED,
            ['status' => $shipment->getOriginal('status')],
            ['status' => 'cancelled'],
            $reason
        );
    }

    public function logPriceOverride(Shipment $shipment, float $originalPrice, float $newPrice, string $reason, int $approvedBy): ShipmentAudit
    {
        return ShipmentAudit::log(
            $shipment->id,
            ShipmentAudit::EVENT_PRICE_OVERRIDE,
            ['price_amount' => $originalPrice],
            [
                'price_amount' => $newPrice,
                'approved_by' => $approvedBy,
            ],
            $reason
        );
    }

    public function getHistory(int $shipmentId): \Illuminate\Database\Eloquent\Collection
    {
        return ShipmentAudit::forShipment($shipmentId)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
