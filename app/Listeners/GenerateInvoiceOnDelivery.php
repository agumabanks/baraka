<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Enums\ShipmentStatus;
use App\Services\InvoiceGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class GenerateInvoiceOnDelivery implements ShouldQueue
{
    protected InvoiceGenerationService $invoiceService;

    public function __construct(InvoiceGenerationService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Handle the event.
     */
    public function handle(ShipmentStatusChanged $event): void
    {
        $shipment = $event->shipment;
        $newStatus = $event->newStatus;

        // Only generate invoice when shipment is delivered
        if ($newStatus !== ShipmentStatus::DELIVERED) {
            return;
        }

        // Skip if invoice already exists
        if ($shipment->invoice_id) {
            return;
        }

        // Skip if no customer
        if (!$shipment->customer_id) {
            Log::warning("Cannot generate invoice for shipment {$shipment->id}: No customer");
            return;
        }

        try {
            $invoice = $this->invoiceService->generateForShipment($shipment);
            
            Log::info("Auto-generated invoice {$invoice->invoice_number} for shipment {$shipment->id}");
        } catch (\Exception $e) {
            Log::error("Failed to generate invoice for shipment {$shipment->id}: {$e->getMessage()}");
        }
    }
}
