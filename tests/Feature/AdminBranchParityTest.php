<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\ShipmentStatus;
use App\Http\Middleware\EnforceBranchIsolation;
use App\Models\Backend\BranchWorker;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Admin-Branch Parity Implementation Tests
 * 
 * Validates P0 and P1 implementations from the parity report.
 */
class AdminBranchParityTest extends TestCase
{
    /**
     * Test ShipmentStatus enum has all required values
     */
    public function test_shipment_status_enum_completeness(): void
    {
        $expectedStatuses = [
            'BOOKED',
            'PICKUP_SCHEDULED',
            'PICKED_UP',
            'AT_ORIGIN_HUB',
            'BAGGED',
            'LINEHAUL_DEPARTED',
            'LINEHAUL_ARRIVED',
            'AT_DESTINATION_HUB',
            'CUSTOMS_HOLD',
            'CUSTOMS_CLEARED',
            'OUT_FOR_DELIVERY',
            'DELIVERED',
            'RETURN_INITIATED',
            'RETURN_IN_TRANSIT',
            'RETURNED',
            'CANCELLED',
            'EXCEPTION',
        ];

        $actualStatuses = array_map(
            fn($case) => $case->value,
            ShipmentStatus::cases()
        );

        $this->assertEquals($expectedStatuses, $actualStatuses, 'ShipmentStatus enum is missing expected values');
    }

    /**
     * Test ShipmentStatus legacy mapping
     */
    public function test_shipment_status_legacy_mapping(): void
    {
        $mappings = [
            'created' => ShipmentStatus::BOOKED,
            'ready_for_pickup' => ShipmentStatus::PICKUP_SCHEDULED,
            'in_transit' => ShipmentStatus::LINEHAUL_DEPARTED,
            'out_for_delivery' => ShipmentStatus::OUT_FOR_DELIVERY,
            'delivered' => ShipmentStatus::DELIVERED,
            'CREATED' => ShipmentStatus::BOOKED,
            'CONFIRMED' => ShipmentStatus::BOOKED,
            'ASSIGNED' => ShipmentStatus::PICKUP_SCHEDULED,
        ];

        foreach ($mappings as $legacy => $expected) {
            $actual = ShipmentStatus::fromString($legacy);
            $this->assertEquals($expected, $actual, "Legacy status '{$legacy}' should map to {$expected->value}");
        }
    }

    /**
     * Test InvoiceStatus enum has all required values
     */
    public function test_invoice_status_enum_completeness(): void
    {
        $expectedStatuses = [
            'DRAFT',
            'PENDING',
            'SENT',
            'PAID',
            'OVERDUE',
            'CANCELLED',
            'REFUNDED',
        ];

        $actualStatuses = array_map(
            fn($case) => $case->value,
            InvoiceStatus::cases()
        );

        $this->assertEquals($expectedStatuses, $actualStatuses, 'InvoiceStatus enum is missing expected values');
    }

    /**
     * Test InvoiceStatus legacy numeric mapping
     */
    public function test_invoice_status_legacy_numeric_mapping(): void
    {
        $mappings = [
            '1' => InvoiceStatus::DRAFT,
            '2' => InvoiceStatus::PENDING,
            '3' => InvoiceStatus::PAID,
            '4' => InvoiceStatus::OVERDUE,
            '5' => InvoiceStatus::CANCELLED,
        ];

        foreach ($mappings as $numeric => $expected) {
            $actual = InvoiceStatus::fromString($numeric);
            $this->assertEquals($expected, $actual, "Numeric status '{$numeric}' should map to {$expected->value}");
        }
    }

    /**
     * Test InvoiceStatus helper methods
     */
    public function test_invoice_status_helper_methods(): void
    {
        $this->assertTrue(InvoiceStatus::PENDING->isPayable());
        $this->assertTrue(InvoiceStatus::SENT->isPayable());
        $this->assertTrue(InvoiceStatus::OVERDUE->isPayable());
        $this->assertFalse(InvoiceStatus::PAID->isPayable());
        
        $this->assertTrue(InvoiceStatus::PAID->isFinal());
        $this->assertTrue(InvoiceStatus::CANCELLED->isFinal());
        $this->assertFalse(InvoiceStatus::PENDING->isFinal());
    }

    /**
     * Test BranchWorker model consolidation
     */
    public function test_branch_worker_model_is_backend_instance(): void
    {
        $this->assertTrue(
            is_a(\App\Models\BranchWorker::class, \App\Models\Backend\BranchWorker::class, true),
            'App\Models\BranchWorker should extend Backend\BranchWorker'
        );
    }

    /**
     * Test BranchWorker has expected methods from Backend model
     */
    public function test_branch_worker_has_canonical_methods(): void
    {
        $methods = [
            'hasPermission',
            'canPerform',
            'getCurrentWorkload',
            'getPerformanceMetrics',
            'isAvailable',
            'assignShipment',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(\App\Models\BranchWorker::class, $method),
                "BranchWorker should have method: {$method}"
            );
        }
    }

    /**
     * Test EnforceBranchIsolation middleware exists
     */
    public function test_branch_isolation_middleware_exists(): void
    {
        $this->assertTrue(
            class_exists(EnforceBranchIsolation::class),
            'EnforceBranchIsolation middleware should exist'
        );
    }

    /**
     * Test branch isolation middleware is registered
     */
    public function test_branch_isolation_middleware_is_registered(): void
    {
        $middlewareAliases = app('router')->getMiddleware();
        
        $this->assertArrayHasKey('branch.isolation', $middlewareAliases, 'branch.isolation middleware should be registered');
        $this->assertEquals(EnforceBranchIsolation::class, $middlewareAliases['branch.isolation']);
    }

    /**
     * Test shared FormRequest classes exist
     */
    public function test_shared_form_requests_exist(): void
    {
        $formRequests = [
            \App\Http\Requests\Shipment\UpdateShipmentStatusRequest::class,
            \App\Http\Requests\Invoice\StoreInvoiceRequest::class,
        ];

        foreach ($formRequests as $formRequest) {
            $this->assertTrue(
                class_exists($formRequest),
                "FormRequest should exist: {$formRequest}"
            );
        }
    }

    /**
     * Test UpdateShipmentStatusRequest has expected methods
     */
    public function test_update_shipment_status_request_has_helpers(): void
    {
        $this->assertTrue(
            method_exists(\App\Http\Requests\Shipment\UpdateShipmentStatusRequest::class, 'getStatus'),
            'UpdateShipmentStatusRequest should have getStatus() method'
        );

        $this->assertTrue(
            method_exists(\App\Http\Requests\Shipment\UpdateShipmentStatusRequest::class, 'getLifecycleContext'),
            'UpdateShipmentStatusRequest should have getLifecycleContext() method'
        );
    }

    /**
     * Test StoreInvoiceRequest has expected methods
     */
    public function test_store_invoice_request_has_helpers(): void
    {
        $this->assertTrue(
            method_exists(\App\Http\Requests\Invoice\StoreInvoiceRequest::class, 'prepareForInvoice'),
            'StoreInvoiceRequest should have prepareForInvoice() method'
        );
    }

    /**
     * Test Invoice model uses InvoiceStatus enum cast
     */
    public function test_invoice_model_uses_status_enum_cast(): void
    {
        $invoice = new Invoice();
        $casts = $invoice->getCasts();

        $this->assertArrayHasKey('status', $casts, 'Invoice model should cast status field');
        $this->assertEquals(InvoiceStatus::class, $casts['status'], 'Invoice status should be cast to InvoiceStatus enum');
    }

    /**
     * Test Invoice model has new helper methods
     */
    public function test_invoice_model_has_new_helper_methods(): void
    {
        $methods = [
            'markAsPaid',
            'markAsOverdue',
            'getStatusLabelAttribute',
            'getStatusBadgeColorAttribute',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(Invoice::class, $method),
                "Invoice model should have method: {$method}"
            );
        }
    }

    /**
     * Test Invoice model has payable scope
     */
    public function test_invoice_model_has_payable_scope(): void
    {
        $this->assertTrue(
            method_exists(Invoice::class, 'scopePayable'),
            'Invoice model should have scopePayable'
        );
    }

    /**
     * Test ShipmentStatus ordered lifecycle
     */
    public function test_shipment_status_ordered_lifecycle(): void
    {
        $lifecycle = ShipmentStatus::orderedLifecycle();

        $this->assertIsArray($lifecycle);
        $this->assertGreaterThan(0, count($lifecycle));
        $this->assertContainsOnlyInstancesOf(ShipmentStatus::class, $lifecycle);
        
        // First status should be BOOKED
        $this->assertEquals(ShipmentStatus::BOOKED, $lifecycle[0]);
    }

    /**
     * Test ShipmentStatus terminal statuses
     */
    public function test_shipment_status_terminal_statuses(): void
    {
        $terminalStatuses = [
            ShipmentStatus::DELIVERED,
            ShipmentStatus::RETURNED,
            ShipmentStatus::CANCELLED,
            ShipmentStatus::EXCEPTION,
        ];

        foreach ($terminalStatuses as $status) {
            $this->assertTrue($status->isTerminal(), "{$status->value} should be terminal");
        }

        $nonTerminalStatuses = [
            ShipmentStatus::BOOKED,
            ShipmentStatus::IN_TRANSIT,
            ShipmentStatus::OUT_FOR_DELIVERY,
        ];

        foreach ($nonTerminalStatuses as $status) {
            $this->assertFalse($status->isTerminal(), "{$status->value} should NOT be terminal");
        }
    }
}
