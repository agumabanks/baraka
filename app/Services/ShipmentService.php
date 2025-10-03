<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ScanEvent;
use App\Models\Bag;
use App\Models\Route;
use App\Models\PodProof;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ShipmentService
{
    /**
     * Create a new shipment with validation and business logic
     */
    public function createShipment(array $data, User $createdBy): Shipment
    {
        // Validate business rules
        $this->validateShipmentCreation($data);

        DB::beginTransaction();
        try {
            $shipment = Shipment::create([
                'customer_id' => $data['customer_id'],
                'origin_branch_id' => $data['origin_branch_id'],
                'dest_branch_id' => $data['dest_branch_id'],
                'service_type' => $data['service_type'],
                'weight' => $data['weight'],
                'dimensions' => $data['dimensions'] ?? null,
                'description' => $data['description'] ?? null,
                'value' => $data['value'] ?? null,
                'cod_amount' => $data['cod_amount'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'created_by' => $createdBy->id,
                'current_status' => 'pending',
                'metadata' => $this->prepareShipmentMetadata($data),
            ]);

            // Create initial scan event
            $this->createInitialScanEvent($shipment, $createdBy);

            // Update customer statistics
            $shipment->customer->updateStatistics();

            DB::commit();

            Log::info('Shipment created successfully', [
                'shipment_id' => $shipment->id,
                'created_by' => $createdBy->id,
                'customer_id' => $shipment->customer_id
            ]);

            return $shipment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shipment creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update shipment with validation
     */
    public function updateShipment(Shipment $shipment, array $data): Shipment
    {
        $this->validateShipmentUpdate($shipment, $data);

        DB::beginTransaction();
        try {
            $oldData = $shipment->toArray();

            $shipment->update([
                'customer_id' => $data['customer_id'] ?? $shipment->customer_id,
                'service_type' => $data['service_type'] ?? $shipment->service_type,
                'weight' => $data['weight'] ?? $shipment->weight,
                'dimensions' => $data['dimensions'] ?? $shipment->dimensions,
                'description' => $data['description'] ?? $shipment->description,
                'value' => $data['value'] ?? $shipment->value,
                'cod_amount' => $data['cod_amount'] ?? $shipment->cod_amount,
                'priority' => $data['priority'] ?? $shipment->priority,
                'metadata' => array_merge($shipment->metadata ?? [], $this->prepareShipmentMetadata($data)),
            ]);

            // Log the update
            Log::info('Shipment updated', [
                'shipment_id' => $shipment->id,
                'old_data' => $oldData,
                'new_data' => $shipment->fresh()->toArray()
            ]);

            DB::commit();

            return $shipment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shipment update failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Assign shipment to a driver/worker
     */
    public function assignToDriver(Shipment $shipment, int $driverId, ?string $notes = null): bool
    {
        $driver = BranchWorker::find($driverId);

        if (!$driver || !$driver->canPerform('assign_shipments')) {
            throw new \Exception('Invalid driver or insufficient permissions');
        }

        if (!$driver->isAvailable()) {
            throw new \Exception('Driver is not available for new assignments');
        }

        DB::beginTransaction();
        try {
            $shipment->update([
                'assigned_worker_id' => $driverId,
                'assigned_at' => now(),
                'current_status' => 'assigned',
            ]);

            // Create scan event
            $this->createScanEvent($shipment, 'assigned', $driver->user, $notes);

            DB::commit();

            Log::info('Shipment assigned to driver', [
                'shipment_id' => $shipment->id,
                'driver_id' => $driverId
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Driver assignment failed', [
                'shipment_id' => $shipment->id,
                'driver_id' => $driverId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bulk assign shipments to driver
     */
    public function bulkAssignToDriver(array $shipmentIds, int $driverId, ?string $notes = null): array
    {
        $driver = BranchWorker::find($driverId);

        if (!$driver || !$driver->canPerform('assign_shipments')) {
            throw new \Exception('Invalid driver or insufficient permissions');
        }

        $result = ['assigned' => 0, 'failed' => 0, 'errors' => []];

        foreach ($shipmentIds as $shipmentId) {
            try {
                $shipment = Shipment::find($shipmentId);
                if ($shipment) {
                    $this->assignToDriver($shipment, $driverId, $notes);
                    $result['assigned']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Shipment {$shipmentId} not found";
                }
            } catch (\Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Shipment {$shipmentId}: {$e->getMessage()}";
            }
        }

        return $result;
    }

    /**
     * Bulk update shipment status
     */
    public function bulkUpdateStatus(array $shipmentIds, string $status, ?string $notes = null, User $updatedBy): array
    {
        $result = ['updated' => 0, 'failed' => 0, 'errors' => []];

        foreach ($shipmentIds as $shipmentId) {
            try {
                $shipment = Shipment::find($shipmentId);
                if ($shipment) {
                    $this->updateShipmentStatus($shipment, $status, $notes, $updatedBy);
                    $result['updated']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Shipment {$shipmentId} not found";
                }
            } catch (\Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Shipment {$shipmentId}: {$e->getMessage()}";
            }
        }

        return $result;
    }

    /**
     * Update shipment status with proper validation and logging
     */
    public function updateShipmentStatus(Shipment $shipment, string $status, ?string $notes = null, User $updatedBy): bool
    {
        $validStatuses = ['pending', 'assigned', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled', 'exception'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status');
        }

        // Validate status transition
        if (!$this->isValidStatusTransition($shipment->current_status, $status)) {
            throw new \Exception("Invalid status transition from {$shipment->current_status} to {$status}");
        }

        DB::beginTransaction();
        try {
            $oldStatus = $shipment->current_status;

            $updateData = ['current_status' => $status];

            // Set delivered timestamp if status is delivered
            if ($status === 'delivered') {
                $updateData['delivered_at'] = now();
            }

            $shipment->update($updateData);

            // Create scan event
            $this->createScanEvent($shipment, $status, $updatedBy, $notes);

            // Fire status change event
            event(new \App\Events\ShipmentStatusChanged($shipment, $oldStatus));

            DB::commit();

            Log::info('Shipment status updated', [
                'shipment_id' => $shipment->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'updated_by' => $updatedBy->id
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Status update failed', [
                'shipment_id' => $shipment->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Assign shipment to bag for consolidation
     */
    public function assignToBag(Shipment $shipment, int $bagId): bool
    {
        $bag = Bag::find($bagId);

        if (!$bag) {
            throw new \Exception('Bag not found');
        }

        if ($bag->is_sealed) {
            throw new \Exception('Bag is already sealed');
        }

        DB::beginTransaction();
        try {
            // Remove from any existing bag
            DB::table('bag_shipment')->where('shipment_id', $shipment->id)->delete();

            // Add to new bag
            DB::table('bag_shipment')->insert([
                'bag_id' => $bagId,
                'shipment_id' => $shipment->id,
                'assigned_at' => now(),
            ]);

            // Create scan event
            $this->createScanEvent($shipment, 'bagged', null, "Assigned to bag {$bag->bag_number}");

            DB::commit();

            Log::info('Shipment assigned to bag', [
                'shipment_id' => $shipment->id,
                'bag_id' => $bagId
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bag assignment failed', [
                'shipment_id' => $shipment->id,
                'bag_id' => $bagId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Add scan event to shipment
     */
    public function addScanEvent(Shipment $shipment, array $data, User $createdBy): ScanEvent
    {
        $this->validateScanEvent($data);

        DB::beginTransaction();
        try {
            $scanEvent = ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => $data['event_type'],
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'user_id' => $createdBy->id,
                'occurred_at' => now(),
            ]);

            // Update shipment status based on scan event
            $shipment->updateStatusFromScan($scanEvent);

            DB::commit();

            Log::info('Scan event added', [
                'shipment_id' => $shipment->id,
                'event_type' => $data['event_type'],
                'user_id' => $createdBy->id
            ]);

            return $scanEvent;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Scan event creation failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify POD (Proof of Delivery)
     */
    public function verifyPod(Shipment $shipment, array $data): bool
    {
        if ($shipment->current_status !== 'delivered') {
            throw new \Exception('Shipment must be delivered before POD verification');
        }

        DB::beginTransaction();
        try {
            PodProof::create([
                'shipment_id' => $shipment->id,
                'verification_method' => $data['verification_method'],
                'verification_data' => $data['verification_data'],
                'notes' => $data['notes'] ?? null,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);

            // Update shipment with verification timestamp
            $shipment->update(['pod_verified_at' => now()]);

            DB::commit();

            Log::info('POD verified', [
                'shipment_id' => $shipment->id,
                'verification_method' => $data['verification_method']
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POD verification failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Resolve shipment exception
     */
    public function resolveException(Shipment $shipment, string $resolution, ?string $notes = null): bool
    {
        if (!$shipment->has_exception) {
            throw new \Exception('Shipment has no active exception');
        }

        DB::beginTransaction();
        try {
            $shipment->update([
                'has_exception' => false,
                'exception_type' => null,
                'exception_severity' => null,
                'exception_notes' => null,
                'exception_resolved_at' => now(),
                'exception_resolution' => $resolution,
            ]);

            // Create scan event
            $this->createScanEvent($shipment, 'exception_resolved', null, $notes);

            DB::commit();

            Log::info('Exception resolved', [
                'shipment_id' => $shipment->id,
                'resolution' => $resolution
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Exception resolution failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate shipment labels
     */
    public function generateLabels(Shipment $shipment): string
    {
        // This would integrate with a label generation service
        // For now, return a placeholder PDF
        return $this->generatePlaceholderLabels($shipment);
    }

    /**
     * Export shipments to various formats
     */
    public function exportShipments($query, string $format = 'csv'): string
    {
        $shipments = $query->with(['customer', 'originBranch', 'destBranch'])->get();

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($shipments);
            case 'excel':
                return $this->exportToExcel($shipments);
            case 'pdf':
                return $this->exportToPdf($shipments);
            default:
                throw new \Exception('Unsupported export format');
        }
    }

    // Private Helper Methods

    private function validateShipmentCreation(array $data): void
    {
        if (!isset($data['customer_id']) || !isset($data['origin_branch_id']) || !isset($data['dest_branch_id'])) {
            throw new \Exception('Missing required shipment data');
        }

        // Validate branch relationships
        $originBranch = Branch::find($data['origin_branch_id']);
        $destBranch = Branch::find($data['dest_branch_id']);

        if (!$originBranch || !$destBranch) {
            throw new \Exception('Invalid origin or destination branch');
        }

        // Validate customer can place order
        $customer = \App\Models\Customer::find($data['customer_id']);
        if (!$customer || !$customer->canPlaceOrder($data['cod_amount'] ?? 0)) {
            throw new \Exception('Customer cannot place this order');
        }
    }

    private function validateShipmentUpdate(Shipment $shipment, array $data): void
    {
        // Prevent updates to delivered shipments
        if ($shipment->current_status === 'delivered') {
            throw new \Exception('Cannot update delivered shipment');
        }

        // Validate customer credit if COD amount changed
        if (isset($data['cod_amount']) && $data['cod_amount'] !== $shipment->cod_amount) {
            $customer = $shipment->customer;
            if (!$customer->canPlaceOrder($data['cod_amount'])) {
                throw new \Exception('Customer credit limit exceeded');
            }
        }
    }

    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['assigned', 'cancelled'],
            'assigned' => ['in_transit', 'cancelled', 'exception'],
            'in_transit' => ['out_for_delivery', 'exception'],
            'out_for_delivery' => ['delivered', 'exception'],
            'delivered' => [], // Final status
            'cancelled' => [], // Final status
            'exception' => ['assigned', 'cancelled'], // Can be reassigned or cancelled
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }

    private function createInitialScanEvent(Shipment $shipment, User $createdBy): void
    {
        ScanEvent::create([
            'shipment_id' => $shipment->id,
            'event_type' => 'created',
            'location' => $shipment->originBranch->name ?? 'Unknown',
            'notes' => 'Shipment created',
            'user_id' => $createdBy->id,
            'occurred_at' => now(),
        ]);
    }

    private function createScanEvent(Shipment $shipment, string $eventType, ?User $user = null, ?string $notes = null): void
    {
        ScanEvent::create([
            'shipment_id' => $shipment->id,
            'event_type' => $eventType,
            'location' => $shipment->originBranch->name ?? 'Unknown',
            'notes' => $notes,
            'user_id' => $user?->id,
            'occurred_at' => now(),
        ]);
    }

    private function validateScanEvent(array $data): void
    {
        $validEventTypes = [
            'created', 'assigned', 'picked_up', 'in_transit', 'arrived',
            'out_for_delivery', 'delivered', 'exception', 'exception_resolved',
            'cancelled', 'returned', 'bagged', 'unbagged'
        ];

        if (!in_array($data['event_type'], $validEventTypes)) {
            throw new \Exception('Invalid scan event type');
        }
    }

    private function prepareShipmentMetadata(array $data): array
    {
        return [
            'created_via' => 'admin',
            'original_data' => $data,
            'created_at' => now()->toISOString(),
        ];
    }

    private function generatePlaceholderLabels(Shipment $shipment): string
    {
        // This would generate actual PDF labels
        // For now, return a simple text representation
        return "Shipment Label for ID: {$shipment->id}";
    }

    private function exportToCsv(Collection $shipments): string
    {
        $filename = 'shipments_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $handle = fopen($filepath, 'w');

        // CSV headers
        fputcsv($handle, [
            'Shipment ID',
            'Customer',
            'Origin Branch',
            'Destination Branch',
            'Status',
            'Weight',
            'Value',
            'COD Amount',
            'Created Date',
        ]);

        // CSV data
        foreach ($shipments as $shipment) {
            fputcsv($handle, [
                $shipment->id,
                $shipment->customer->company_name ?? $shipment->customer->contact_person,
                $shipment->originBranch->name ?? 'Unknown',
                $shipment->destBranch->name ?? 'Unknown',
                $shipment->current_status,
                $shipment->weight,
                $shipment->value,
                $shipment->cod_amount,
                $shipment->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($handle);

        return $filepath;
    }

    private function exportToExcel(Collection $shipments): string
    {
        // This would use Laravel Excel package
        // For now, return CSV as placeholder
        return $this->exportToCsv($shipments);
    }

    private function exportToPdf(Collection $shipments): string
    {
        // This would generate PDF reports
        // For now, return CSV as placeholder
        return $this->exportToCsv($shipments);
    }
}