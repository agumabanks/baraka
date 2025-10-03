<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ScanEvent;
use App\Models\Bag;
use App\Models\Route;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class UnifiedShipmentWorkflowService
{
    /**
     * Process shipment through the unified branch workflow
     */
    public function processShipmentWorkflow(Shipment $shipment, string $action, array $data = []): array
    {
        return match($action) {
            'create' => $this->handleShipmentCreation($shipment, $data),
            'pickup' => $this->handlePickup($shipment, $data),
            'transfer_to_hub' => $this->handleTransferToHub($shipment, $data),
            'hub_processing' => $this->handleHubProcessing($shipment, $data),
            'transfer_to_destination' => $this->handleTransferToDestination($shipment, $data),
            'delivery' => $this->handleDelivery($shipment, $data),
            'exception' => $this->handleException($shipment, $data),
            'return' => $this->handleReturn($shipment, $data),
            default => throw new \Exception('Invalid workflow action'),
        };
    }

    /**
     * Handle shipment creation in unified system
     */
    private function handleShipmentCreation(Shipment $shipment, array $data): array
    {
        DB::beginTransaction();
        try {
            // Validate branch relationships
            $this->validateBranchRelationship($shipment->originBranch, $shipment->destBranch);

            // Set initial status based on branch type
            $initialStatus = $this->determineInitialStatus($shipment);
            $shipment->update(['current_status' => $initialStatus]);

            // Create initial scan event
            ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'created',
                'location' => $shipment->originBranch->name,
                'notes' => 'Shipment created in unified system',
                'user_id' => $data['created_by'] ?? null,
                'occurred_at' => now(),
            ]);

            // If origin is HUB, process immediately
            if ($shipment->originBranch->is_hub) {
                $this->handleHubProcessing($shipment, $data);
            }

            DB::commit();

            return [
                'success' => true,
                'status' => $initialStatus,
                'message' => 'Shipment created and processed through unified workflow',
                'next_actions' => $this->getNextActions($shipment),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shipment creation workflow failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle pickup process
     */
    private function handlePickup(Shipment $shipment, array $data): array
    {
        $pickupWorker = BranchWorker::find($data['worker_id']);

        if (!$pickupWorker || !$pickupWorker->canPerform('pickup_shipments')) {
            throw new \Exception('Invalid pickup worker');
        }

        DB::beginTransaction();
        try {
            // Update shipment status
            $shipment->update([
                'current_status' => 'picked_up',
                'picked_up_at' => now(),
            ]);

            // Create scan event
            ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'picked_up',
                'location' => $shipment->originBranch->name,
                'notes' => $data['notes'] ?? 'Shipment picked up',
                'user_id' => $pickupWorker->user_id,
                'occurred_at' => now(),
            ]);

            // Determine next step based on destination
            $nextAction = $this->determineNextAction($shipment);

            DB::commit();

            return [
                'success' => true,
                'status' => 'picked_up',
                'next_action' => $nextAction,
                'message' => 'Shipment picked up successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle transfer to HUB
     */
    private function handleTransferToHub(Shipment $shipment, array $data): array
    {
        $hub = Branch::hub()->first();

        if (!$hub) {
            throw new \Exception('No HUB branch configured');
        }

        DB::beginTransaction();
        try {
            // Update shipment for HUB transfer
            $shipment->update([
                'current_status' => 'in_transit_to_hub',
                'transfer_hub_id' => $hub->id,
            ]);

            // Create scan event
            ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'transfer_to_hub',
                'location' => $shipment->originBranch->name,
                'notes' => 'Shipment transferred to HUB for processing',
                'user_id' => $data['user_id'] ?? null,
                'occurred_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'in_transit_to_hub',
                'hub_id' => $hub->id,
                'message' => 'Shipment transferred to HUB',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle HUB processing and sortation
     */
    private function handleHubProcessing(Shipment $shipment, array $data): array
    {
        $hub = Branch::hub()->first();

        if (!$hub) {
            throw new \Exception('No HUB branch configured');
        }

        DB::beginTransaction();
        try {
            // Update shipment status
            $shipment->update([
                'current_status' => 'at_hub',
                'hub_processed_at' => now(),
            ]);

            // Create scan event
            ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'arrived_at_hub',
                'location' => $hub->name,
                'notes' => 'Shipment arrived at HUB for processing',
                'user_id' => $data['user_id'] ?? null,
                'occurred_at' => now(),
            ]);

            // Determine destination routing
            $routing = $this->determineDestinationRouting($shipment);

            DB::commit();

            return [
                'success' => true,
                'status' => 'at_hub',
                'routing' => $routing,
                'message' => 'Shipment processed at HUB',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle transfer to destination branch
     */
    private function handleTransferToDestination(Shipment $shipment, array $data): array
    {
        DB::beginTransaction();
        try {
            // Update shipment status
            $shipment->update([
                'current_status' => 'in_transit_to_destination',
                'transferred_at' => now(),
            ]);

            // Create scan event
            ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'transfer_to_destination',
                'location' => $shipment->originBranch->name,
                'notes' => 'Shipment transferred to destination branch',
                'user_id' => $data['user_id'] ?? null,
                'occurred_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'in_transit_to_destination',
                'message' => 'Shipment transferred to destination',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle delivery process
     */
    private function handleDelivery(Shipment $shipment, array $data): array
    {
        $deliveryWorker = BranchWorker::find($data['worker_id']);

        if (!$deliveryWorker || !$deliveryWorker->canPerform('deliver_shipments')) {
            throw new \Exception('Invalid delivery worker');
        }

        DB::beginTransaction();
        try {
            // Update shipment status
            $shipment->update([
                'current_status' => 'delivered',
                'delivered_at' => now(),
                'delivered_by' => $deliveryWorker->user_id,
            ]);

            // Create scan event
            ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'delivered',
                'location' => $shipment->destBranch->name,
                'notes' => $data['notes'] ?? 'Shipment delivered',
                'user_id' => $deliveryWorker->user_id,
                'occurred_at' => now(),
            ]);

            // Update customer statistics
            $shipment->customer->updateStatistics();

            DB::commit();

            return [
                'success' => true,
                'status' => 'delivered',
                'delivered_by' => $deliveryWorker->user->name,
                'message' => 'Shipment delivered successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle shipment exception
     */
    private function handleException(Shipment $shipment, array $data): array
    {
        DB::beginTransaction();
        try {
            $exceptionType = $data['exception_type'] ?? 'general';
            $severity = $data['severity'] ?? 'medium';

            // Update shipment
            $shipment->update([
                'current_status' => 'exception',
                'has_exception' => true,
                'exception_type' => $exceptionType,
                'exception_severity' => $severity,
                'exception_notes' => $data['notes'] ?? null,
                'exception_occurred_at' => now(),
            ]);

            // Create scan event
            ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'exception',
                'location' => $shipment->originBranch->name,
                'notes' => "Exception: {$exceptionType} - " . ($data['notes'] ?? ''),
                'user_id' => $data['user_id'] ?? null,
                'occurred_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'exception',
                'exception_type' => $exceptionType,
                'severity' => $severity,
                'message' => 'Exception recorded',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle shipment return process
     */
    private function handleReturn(Shipment $shipment, array $data): array
    {
        DB::beginTransaction();
        try {
            // Update shipment
            $shipment->update([
                'current_status' => 'returned',
                'returned_at' => now(),
                'return_reason' => $data['reason'] ?? null,
                'return_notes' => $data['notes'] ?? null,
            ]);

            // Create scan event
            ScanEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'returned',
                'location' => $shipment->destBranch->name,
                'notes' => 'Shipment returned - ' . ($data['reason'] ?? ''),
                'user_id' => $data['user_id'] ?? null,
                'occurred_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'returned',
                'reason' => $data['reason'] ?? null,
                'message' => 'Shipment returned',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get next actions for a shipment based on current status and branch
     */
    private function getNextActions(Shipment $shipment): array
    {
        $actions = [];

        switch ($shipment->current_status) {
            case 'pending':
                if ($shipment->originBranch->is_hub) {
                    $actions[] = 'process_at_hub';
                } else {
                    $actions[] = 'schedule_pickup';
                }
                break;

            case 'assigned':
                $actions[] = 'pickup';
                break;

            case 'picked_up':
                if (!$shipment->originBranch->is_hub) {
                    $actions[] = 'transfer_to_hub';
                } else {
                    $actions[] = 'hub_processing';
                }
                break;

            case 'at_hub':
                $actions[] = 'transfer_to_destination';
                break;

            case 'in_transit_to_destination':
                $actions[] = 'assign_to_destination_worker';
                break;

            case 'assigned_to_destination':
                $actions[] = 'out_for_delivery';
                break;

            case 'out_for_delivery':
                $actions[] = 'deliver';
                break;
        }

        return $actions;
    }

    /**
     * Determine initial status based on branch configuration
     */
    private function determineInitialStatus(Shipment $shipment): string
    {
        // If origin is HUB, shipment is ready for processing
        if ($shipment->originBranch->is_hub) {
            return 'ready_for_processing';
        }

        // If destination is HUB, shipment needs pickup
        if ($shipment->destBranch->is_hub) {
            return 'pending_pickup';
        }

        // Otherwise, standard pending status
        return 'pending';
    }

    /**
     * Determine next action based on shipment routing
     */
    private function determineNextAction(Shipment $shipment): string
    {
        if ($shipment->destBranch->is_hub) {
            return 'transfer_to_hub';
        }

        return 'transfer_to_destination';
    }

    /**
     * Validate branch relationship for shipment
     */
    private function validateBranchRelationship(Branch $origin, Branch $destination): void
    {
        // HUB can send to any branch
        if ($origin->is_hub) {
            return;
        }

        // Non-HUB branches can only send to HUB or their parent branches
        if (!$destination->is_hub && $destination->id !== $origin->parent_branch_id) {
            throw new \Exception('Invalid branch relationship for shipment');
        }
    }

    /**
     * Determine destination routing for HUB processing
     */
    private function determineDestinationRouting(Shipment $shipment): array
    {
        $destination = $shipment->destBranch;

        // If destination is HUB, route to HUB
        if ($destination->is_hub) {
            return [
                'route_type' => 'direct_to_hub',
                'next_branch' => $destination,
                'estimated_time' => $this->calculateTransitTime($shipment->originBranch, $destination),
            ];
        }

        // If destination is regional, route through regional branch
        if ($destination->type === 'REGIONAL') {
            return [
                'route_type' => 'to_regional',
                'next_branch' => $destination,
                'estimated_time' => $this->calculateTransitTime($shipment->originBranch, $destination),
            ];
        }

        // If destination is local, route through parent regional branch
        if ($destination->type === 'LOCAL' && $destination->parent) {
            return [
                'route_type' => 'to_regional_then_local',
                'next_branch' => $destination->parent,
                'final_destination' => $destination,
                'estimated_time' => $this->calculateTransitTime($shipment->originBranch, $destination->parent),
            ];
        }

        throw new \Exception('Cannot determine routing for destination branch');
    }

    /**
     * Calculate transit time between branches
     */
    private function calculateTransitTime(Branch $from, Branch $to): float
    {
        $distance = $from->distanceTo($to);

        // Assume average speed of 40 km/h for ground transport
        return round($distance / 40, 2);
    }

    /**
     * Get shipment workflow status
     */
    public function getShipmentWorkflowStatus(Shipment $shipment): array
    {
        $workflow = [
            'current_status' => $shipment->current_status,
            'current_location' => $this->getCurrentLocation($shipment),
            'next_actions' => $this->getNextActions($shipment),
            'estimated_completion' => $this->estimateCompletionTime($shipment),
            'branch_path' => $this->getBranchPath($shipment),
            'workflow_steps' => $this->getWorkflowSteps($shipment),
        ];

        return $workflow;
    }

    /**
     * Get current location of shipment
     */
    private function getCurrentLocation(Shipment $shipment): string
    {
        $lastScan = $shipment->scanEvents()->latest('occurred_at')->first();

        if ($lastScan) {
            return $lastScan->location ?? 'Unknown';
        }

        return $shipment->originBranch->name ?? 'Unknown';
    }

    /**
     * Estimate completion time
     */
    private function estimateCompletionTime(Shipment $shipment): ?Carbon
    {
        $baseTime = now();

        switch ($shipment->current_status) {
            case 'pending':
                return $baseTime->addHours(24);
            case 'assigned':
                return $baseTime->addHours(12);
            case 'picked_up':
                return $baseTime->addHours(8);
            case 'at_hub':
                return $baseTime->addHours(6);
            case 'in_transit_to_destination':
                return $baseTime->addHours(4);
            case 'out_for_delivery':
                return $baseTime->addHours(2);
            default:
                return null;
        }
    }

    /**
     * Get branch path for shipment
     */
    private function getBranchPath(Shipment $shipment): array
    {
        $path = [
            [
                'branch' => $shipment->originBranch,
                'action' => 'origin',
                'timestamp' => $shipment->created_at,
            ]
        ];

        // Add HUB if shipment goes through HUB
        if (!$shipment->originBranch->is_hub && !$shipment->destBranch->is_hub) {
            $hub = Branch::hub()->first();
            if ($hub) {
                $path[] = [
                    'branch' => $hub,
                    'action' => 'hub_processing',
                    'timestamp' => null,
                ];
            }
        }

        $path[] = [
            'branch' => $shipment->destBranch,
            'action' => 'destination',
            'timestamp' => null,
        ];

        return $path;
    }

    /**
     * Get workflow steps with status
     */
    private function getWorkflowSteps(Shipment $shipment): array
    {
        $steps = [
            [
                'step' => 'created',
                'label' => 'Shipment Created',
                'status' => 'completed',
                'timestamp' => $shipment->created_at,
            ],
            [
                'step' => 'processed',
                'label' => 'Processed at Origin',
                'status' => $this->getStepStatus($shipment, 'processed'),
                'timestamp' => $shipment->processed_at,
            ],
            [
                'step' => 'at_hub',
                'label' => 'At HUB',
                'status' => $this->getStepStatus($shipment, 'at_hub'),
                'timestamp' => $shipment->hub_processed_at,
            ],
            [
                'step' => 'in_transit',
                'label' => 'In Transit',
                'status' => $this->getStepStatus($shipment, 'in_transit'),
                'timestamp' => $shipment->transferred_at,
            ],
            [
                'step' => 'delivered',
                'label' => 'Delivered',
                'status' => $this->getStepStatus($shipment, 'delivered'),
                'timestamp' => $shipment->delivered_at,
            ],
        ];

        return $steps;
    }

    /**
     * Get step status
     */
    private function getStepStatus(Shipment $shipment, string $step): string
    {
        $timestamp = match($step) {
            'processed' => $shipment->processed_at,
            'at_hub' => $shipment->hub_processed_at,
            'in_transit' => $shipment->transferred_at,
            'delivered' => $shipment->delivered_at,
            default => null,
        };

        if ($timestamp) {
            return 'completed';
        }

        // Check if step is current
        $currentSteps = match($shipment->current_status) {
            'pending' => ['processed'],
            'assigned' => ['processed'],
            'picked_up' => ['processed', 'at_hub'],
            'at_hub' => ['processed', 'at_hub'],
            'in_transit_to_destination' => ['processed', 'at_hub', 'in_transit'],
            'out_for_delivery' => ['processed', 'at_hub', 'in_transit'],
            'delivered' => ['processed', 'at_hub', 'in_transit', 'delivered'],
            default => [],
        };

        return in_array($step, $currentSteps) ? 'current' : 'pending';
    }

    /**
     * Get shipments for HUB sortation
     */
    public function getHubSortationShipments(): Collection
    {
        $hub = Branch::hub()->first();

        if (!$hub) {
            return collect();
        }

        return Shipment::where('dest_branch_id', $hub->id)
            ->orWhere('transfer_hub_id', $hub->id)
            ->whereIn('current_status', ['pending', 'assigned', 'picked_up'])
            ->with(['originBranch', 'destBranch', 'customer'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Process HUB sortation
     */
    public function processHubSortation(array $shipmentIds, array $data): array
    {
        $shipments = Shipment::whereIn('id', $shipmentIds)->get();

        $result = [
            'processed' => 0,
            'errors' => [],
            'sortation_groups' => [],
        ];

        // Group shipments by destination
        $groupedShipments = $shipments->groupBy('dest_branch_id');

        foreach ($groupedShipments as $destBranchId => $destShipments) {
            try {
                $destBranch = Branch::find($destBranchId);

                $sortationGroup = [
                    'destination' => $destBranch->name,
                    'shipments' => $destShipments->count(),
                    'priority_breakdown' => $destShipments->groupBy('priority')->map->count(),
                ];

                // Process each shipment in the group
                foreach ($destShipments as $shipment) {
                    $this->processShipmentWorkflow($shipment, 'hub_processing', $data);
                    $result['processed']++;
                }

                $result['sortation_groups'][] = $sortationGroup;

            } catch (\Exception $e) {
                $result['errors'][] = "Failed to process shipments for destination {$destBranchId}: {$e->getMessage()}";
            }
        }

        return $result;
    }

    /**
     * Get inter-branch transfer shipments
     */
    public function getInterBranchTransfers(): Collection
    {
        return Shipment::where('origin_branch_id', '!=', 'dest_branch_id')
            ->whereIn('current_status', ['in_transit_to_hub', 'in_transit_to_destination'])
            ->with(['originBranch', 'destBranch', 'customer'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Process inter-branch transfer
     */
    public function processInterBranchTransfer(Shipment $shipment, array $data): array
    {
        $fromBranch = $shipment->originBranch;
        $toBranch = $shipment->destBranch;

        // Validate transfer
        if (!$this->canTransferBetweenBranches($fromBranch, $toBranch)) {
            throw new \Exception('Invalid branch transfer');
        }

        return $this->processShipmentWorkflow($shipment, 'transfer_to_destination', $data);
    }

    /**
     * Check if transfer between branches is allowed
     */
    private function canTransferBetweenBranches(Branch $from, Branch $to): bool
    {
        // HUB can transfer to any branch
        if ($from->is_hub) {
            return true;
        }

        // Non-HUB branches can only transfer to HUB
        return $to->is_hub;
    }

    /**
     * Get branch worker assignments for shipments
     */
    public function getWorkerAssignments(Branch $branch): array
    {
        $workers = $branch->activeWorkers()->with(['user', 'assignedShipments'])->get();

        $assignments = [];

        foreach ($workers as $worker) {
            $activeShipments = $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();

            $assignments[] = [
                'worker' => $worker,
                'active_shipments' => $activeShipments,
                'capacity' => $this->getWorkerCapacity($worker),
                'utilization_rate' => $this->calculateWorkerUtilization($worker),
                'next_available' => $this->getWorkerNextAvailable($worker),
            ];
        }

        return $assignments;
    }

    /**
     * Get worker capacity
     */
    public function getWorkerCapacity(BranchWorker $worker): int
    {
        return match($worker->role) {
            'dispatcher' => 50,
            'driver' => 15,
            'supervisor' => 30,
            'warehouse_worker' => 25,
            'customer_service' => 20,
            default => 10,
        };
    }

    /**
     * Calculate worker utilization
     */
    private function calculateWorkerUtilization(BranchWorker $worker): float
    {
        $activeShipments = $worker->assignedShipments()
            ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->count();

        $capacity = $this->getWorkerCapacity($worker);

        return $capacity > 0 ? round(($activeShipments / $capacity) * 100, 2) : 0;
    }

    /**
     * Get worker next available time
     */
    private function getWorkerNextAvailable(BranchWorker $worker): ?Carbon
    {
        $lastShipment = $worker->assignedShipments()
            ->where('current_status', 'delivered')
            ->latest('delivered_at')
            ->first();

        if (!$lastShipment || !$lastShipment->delivered_at) {
            return now();
        }

        // Assume 30 minutes between deliveries
        return $lastShipment->delivered_at->addMinutes(30);
    }

    /**
     * Get HUB distribution summary
     */
    public function getHubDistributionSummary(): array
    {
        $hub = Branch::hub()->first();

        if (!$hub) {
            return ['error' => 'No HUB configured'];
        }

        $incomingShipments = $this->getHubSortationShipments();
        $outgoingShipments = $hub->originShipments()
            ->whereIn('current_status', ['in_transit_to_destination', 'out_for_delivery'])
            ->get();

        $distributionByDestination = $outgoingShipments->groupBy('dest_branch_id')->map->count();

        return [
            'hub' => $hub,
            'incoming_shipments' => $incomingShipments->count(),
            'outgoing_shipments' => $outgoingShipments->count(),
            'pending_sortation' => $incomingShipments->count(),
            'distribution_by_destination' => $distributionByDestination,
            'processing_capacity' => $this->getHubProcessingCapacity($hub),
        ];
    }

    /**
     * Get HUB processing capacity
     */
    private function getHubProcessingCapacity(Branch $hub): array
    {
        $workers = $hub->activeWorkers()->get();
        $totalCapacity = $workers->sum(function ($worker) {
            return $this->getWorkerCapacity($worker);
        });

        $currentLoad = $hub->originShipments()
            ->whereIn('current_status', ['at_hub', 'in_transit_to_destination'])
            ->count();

        return [
            'total_capacity' => $totalCapacity,
            'current_load' => $currentLoad,
            'available_capacity' => max(0, $totalCapacity - $currentLoad),
            'utilization_rate' => $totalCapacity > 0 ? round(($currentLoad / $totalCapacity) * 100, 2) : 0,
        ];
    }

    /**
     * Get shipment workflow analytics
     */
    public function getWorkflowAnalytics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $shipments = Shipment::where('created_at', '>=', $startDate)->get();

        $statusDistribution = $shipments->groupBy('current_status')->map->count();

        $avgProcessingTime = $this->calculateAverageProcessingTime($shipments);
        $onTimeDeliveryRate = $this->calculateOnTimeDeliveryRate($shipments);

        return [
            'period_days' => $days,
            'total_shipments' => $shipments->count(),
            'status_distribution' => $statusDistribution,
            'average_processing_time_hours' => $avgProcessingTime,
            'on_time_delivery_rate' => $onTimeDeliveryRate,
            'branch_efficiency' => $this->calculateBranchEfficiency($shipments),
            'workflow_bottlenecks' => $this->identifyWorkflowBottlenecks($shipments),
        ];
    }

    /**
     * Calculate average processing time
     */
    private function calculateAverageProcessingTime(Collection $shipments): float
    {
        $processedShipments = $shipments->where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('created_at');

        if ($processedShipments->isEmpty()) {
            return 0;
        }

        $totalHours = $processedShipments->sum(function ($shipment) {
            return $shipment->created_at->diffInHours($shipment->delivered_at);
        });

        return round($totalHours / $processedShipments->count(), 1);
    }

    /**
     * Calculate on-time delivery rate
     */
    private function calculateOnTimeDeliveryRate(Collection $shipments): float
    {
        $deliveredShipments = $shipments->where('current_status', 'delivered');

        if ($deliveredShipments->isEmpty()) {
            return 0;
        }

        $onTimeDeliveries = $deliveredShipments->filter(function ($shipment) {
            return $shipment->delivered_at &&
                   $shipment->expected_delivery_date &&
                   $shipment->delivered_at <= $shipment->expected_delivery_date;
        })->count();

        return round(($onTimeDeliveries / $deliveredShipments->count()) * 100, 1);
    }

    /**
     * Calculate branch efficiency
     */
    private function calculateBranchEfficiency(Collection $shipments): array
    {
        $branchStats = $shipments->groupBy('origin_branch_id')->map(function ($branchShipments) {
            $branch = $branchShipments->first()->originBranch;

            return [
                'branch' => $branch->name,
                'shipment_count' => $branchShipments->count(),
                'avg_processing_time' => $this->calculateAverageProcessingTime($branchShipments),
                'on_time_rate' => $this->calculateOnTimeDeliveryRate($branchShipments),
            ];
        });

        return $branchStats->sortByDesc('shipment_count')->values()->toArray();
    }

    /**
     * Identify workflow bottlenecks
     */
    private function identifyWorkflowBottlenecks(Collection $shipments): array
    {
        $bottlenecks = [];

        // Check for stuck shipments
        $stuckShipments = $shipments->filter(function ($shipment) {
            $hoursSinceUpdate = $shipment->updated_at->diffInHours(now());
            return $hoursSinceUpdate > 48 && !in_array($shipment->current_status, ['delivered', 'cancelled']);
        });

        if ($stuckShipments->count() > 0) {
            $bottlenecks[] = [
                'type' => 'stuck_shipments',
                'count' => $stuckShipments->count(),
                'description' => 'Shipments stuck in workflow for more than 48 hours',
            ];
        }

        // Check for exception rate
        $exceptionRate = $shipments->where('has_exception', true)->count() / max(1, $shipments->count()) * 100;
        if ($exceptionRate > 10) {
            $bottlenecks[] = [
                'type' => 'high_exception_rate',
                'rate' => round($exceptionRate, 1),
                'description' => 'High exception rate detected',
            ];
        }

        return $bottlenecks;
    }
}