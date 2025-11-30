<?php

namespace App\Observers;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Branch\FinanceController;
use App\Models\Shipment;

class ShipmentInvoiceObserver
{
    public function updated(Shipment $shipment): void
    {
        $status = $shipment->current_status instanceof ShipmentStatus
            ? $shipment->current_status
            : ShipmentStatus::fromString((string) $shipment->current_status);

        if ($status === ShipmentStatus::DELIVERED) {
            try {
                app(FinanceController::class)->autoInvoice($shipment);
            } catch (\Throwable $e) {
                \Log::warning('Auto-invoice failed', [
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
