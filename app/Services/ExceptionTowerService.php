<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\User;
use App\Events\ExceptionCreatedEvent;
use App\Events\OperationalAlertEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExceptionTowerService
{
    /**
     * Get active exceptions with filtering
     * Note: Exception columns don't exist in current schema, returning empty collection
     */
    public function getActiveExceptions(array $filters = []): Collection
    {
        // The shipments table doesn't have exception-specific columns yet
        // Return empty collection gracefully
        Log::info('ExceptionTowerService: Exception columns not yet implemented in database schema');
        return collect();
    }

    /**
     * Create a new exception
     * Note: Exception columns don't exist yet, returning mock response
     */
    public function createException(Shipment $shipment, array $data): array
    {
        Log::info('ExceptionTowerService: Exception columns not yet implemented in database schema');
        return [
            'success' => false,
            'message' => 'Exception tracking not yet implemented in database schema',
        ];
        
        /* Original code - will be enabled when exception columns are added
        $exceptionType = $data['type'] ?? 'general';
        $severity = $this->determineSeverity($shipment, $data);
        $priority = $this->calculatePriority($severity, $shipment);

        DB::beginTransaction();
        try {
            $shipment->update([
                'has_exception' => true,
                'exception_type' => $exceptionType,
                'exception_severity' => $severity,
                'exception_notes' => $data['notes'] ?? null,
                'exception_occurred_at' => now(),
                'current_status' => 'exception',
            ]);

            // Log the exception
            activity()
                ->performedOn($shipment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'exception_type' => $exceptionType,
                    'severity' => $severity,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->user()->name,
                ])
                ->log("Exception created: {$exceptionType} ({$severity})");

            DB::commit();

            $exception = $this->formatException($shipment);

            // Fire real-time notification event
            broadcast(new ExceptionCreatedEvent($shipment, $exception))->toOthers();

            // Send alert for high-priority exceptions
            if ($priority >= 4) {
                $this->sendExceptionAlert($shipment, $exception);
            }

            return [
                'success' => true,
                'exception' => $exception,
                'message' => 'Exception created successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Exception creation failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create exception: ' . $e->getMessage(),
            ];
        }
        */
    }

    /**
     * Assign exception to resolver
     * Note: Exception columns don't exist yet, returning mock response
     */
    public function assignExceptionToResolver(Shipment $shipment, User $resolver): array
    {
        Log::info('ExceptionTowerService: Exception columns not yet implemented');
        return [
            'success' => false,
            'message' => 'Exception tracking not yet implemented in database schema',
        ];
        
        /* Original code - will be enabled when exception columns are added
        if (!$shipment->has_exception) {
            return [
                'success' => false,
                'message' => 'Shipment does not have an active exception',
            ];
        }

        DB::beginTransaction();
        try {
            $shipment->update([
                'assigned_exception_resolver_id' => $resolver->id,
                'exception_assigned_at' => now(),
            ]);

            // Log the assignment
            activity()
                ->performedOn($shipment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'resolver_id' => $resolver->id,
                    'resolver_name' => $resolver->name,
                    'assigned_by' => auth()->user()->name,
                ])
                ->log("Exception assigned to resolver: {$resolver->name}");

            DB::commit();

            return [
                'success' => true,
                'message' => 'Exception assigned to resolver successfully',
                'assignment' => [
                    'shipment_id' => $shipment->id,
                    'resolver_id' => $resolver->id,
                    'resolver_name' => $resolver->name,
                    'assigned_at' => $shipment->exception_assigned_at->toISOString(),
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Exception assignment failed', [
                'shipment_id' => $shipment->id,
                'resolver_id' => $resolver->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to assign exception: ' . $e->getMessage(),
            ];
        }
        */
    }

    /**
     * Update exception status
     * Note: Exception columns don't exist yet, returning mock response
     */
    public function updateExceptionStatus(Shipment $shipment, string $status, array $data = []): array
    {
        Log::info('ExceptionTowerService: Exception columns not yet implemented');
        return [
            'success' => false,
            'message' => 'Exception tracking not yet implemented in database schema',
        ];
        
        /* Original code - will be enabled when exception columns are added
        if (!$shipment->has_exception) {
            return [
                'success' => false,
                'message' => 'Shipment does not have an active exception',
            ];
        }

        $validStatuses = ['investigating', 'resolved', 'escalated', 'closed'];
        if (!in_array($status, $validStatuses)) {
            return [
                'success' => false,
                'message' => 'Invalid exception status',
            ];
        }

        DB::beginTransaction();
        try {
            $updates = [
                'exception_status' => $status,
                'exception_updated_at' => now(),
            ];

            if (isset($data['resolution_notes'])) {
                $updates['exception_resolution_notes'] = $data['resolution_notes'];
            }

            if ($status === 'resolved' || $status === 'closed') {
                $updates['exception_resolved_at'] = now();
                $updates['has_exception'] = false; // Clear exception flag
                $updates['current_status'] = $data['new_shipment_status'] ?? 'pending';
            }

            $shipment->update($updates);

            // Log the status update
            activity()
                ->performedOn($shipment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_status' => $shipment->getOriginal('exception_status'),
                    'new_status' => $status,
                    'resolution_notes' => $data['resolution_notes'] ?? null,
                    'updated_by' => auth()->user()->name,
                ])
                ->log("Exception status updated to: {$status}");

            DB::commit();

            return [
                'success' => true,
                'message' => 'Exception status updated successfully',
                'update' => [
                    'shipment_id' => $shipment->id,
                    'old_status' => $shipment->getOriginal('exception_status'),
                    'new_status' => $status,
                    'updated_at' => $shipment->exception_updated_at->toISOString(),
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Exception status update failed', [
                'shipment_id' => $shipment->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update exception status: ' . $e->getMessage(),
            ];
        }
        */
    }

    /**
     * Get exception metrics
     * Note: Exception columns don't exist in current schema, returning default metrics
     */
    public function getExceptionMetrics(Carbon $startDate, Carbon $endDate): array
    {
        // Return default metrics since exception columns don't exist yet
        return [
            'total_exceptions' => 0,
            'resolved_exceptions' => 0,
            'pending_exceptions' => 0,
            'average_resolution_time_hours' => 0,
            'exceptions_by_type' => [],
            'exceptions_by_severity' => [],
            'exceptions_by_branch' => [],
            'resolution_rate' => 0,
        ];
        
        /* Original code - will be enabled when exception columns are added
        try {
        $exceptions = Shipment::where('has_exception', true)
            ->whereBetween('exception_occurred_at', [$startDate, $endDate])
            ->get();

        $resolvedExceptions = $exceptions->whereNotNull('exception_resolved_at');

        $byType = $exceptions->groupBy('exception_type')->map->count();
        $bySeverity = $exceptions->groupBy('exception_severity')->map->count();
        $byBranch = $exceptions->groupBy(function ($exception) {
            return $exception->originBranch->name ?? 'Unknown';
        })->map->count();

        $resolutionTime = $resolvedExceptions->map(function ($exception) {
            return $exception->exception_occurred_at->diffInHours($exception->exception_resolved_at);
        });

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_exceptions' => $exceptions->count(),
                'resolved_exceptions' => $resolvedExceptions->count(),
                'unresolved_exceptions' => $exceptions->count() - $resolvedExceptions->count(),
                'resolution_rate' => $exceptions->count() > 0 ?
                    round(($resolvedExceptions->count() / $exceptions->count()) * 100, 1) : 0,
            ],
            'breakdown' => [
                'by_type' => $byType,
                'by_severity' => $bySeverity,
                'by_branch' => $byBranch,
            ],
            'performance' => [
                'average_resolution_time_hours' => $resolutionTime->avg() ?? 0,
                'median_resolution_time_hours' => $resolutionTime->median() ?? 0,
                'fastest_resolution_hours' => $resolutionTime->min() ?? 0,
                'slowest_resolution_hours' => $resolutionTime->max() ?? 0,
            ],
            'trends' => $this->calculateExceptionTrends($exceptions, $startDate, $endDate),
        ];
        */
    }

    /**
     * Get priority exceptions requiring immediate attention
     * Note: Exception columns don't exist yet, returning empty collection
     */
    public function getPriorityExceptions(): Collection
    {
        Log::info('ExceptionTowerService: Exception columns not yet implemented');
        return collect();
    }

    /**
     * Auto-detect exceptions based on shipment conditions
     * Note: Exception columns don't exist yet, returning empty array
     */
    public function detectExceptions(): array
    {
        Log::info('ExceptionTowerService: Exception columns not yet implemented');
        return [
            'detected_count' => 0,
            'exceptions' => [],
        ];
        
        /* Original code - will be enabled when exception columns are added
        $detectedExceptions = [];

        // Detect delayed shipments
        $delayedShipments = Shipment::where('expected_delivery_date', '<', now())
            ->whereNotIn('current_status', ['delivered', 'cancelled', 'returned'])
            ->where('has_exception', false)
            ->get();

        foreach ($delayedShipments as $shipment) {
            $daysDelayed = now()->diffInDays($shipment->expected_delivery_date);
            $severity = $daysDelayed > 3 ? 'high' : 'medium';

            $detectedExceptions[] = [
                'shipment' => $shipment,
                'type' => 'delayed_delivery',
                'severity' => $severity,
                'notes' => "Shipment is {$daysDelayed} days past expected delivery date",
                'auto_detected' => true,
            ];
        }

        // Detect stuck shipments (no status change in 48 hours)
        $stuckShipments = Shipment::where('updated_at', '<', now()->subHours(48))
            ->whereNotIn('current_status', ['delivered', 'cancelled', 'returned'])
            ->where('has_exception', false)
            ->get();

        foreach ($stuckShipments as $shipment) {
            $hoursStuck = now()->diffInHours($shipment->updated_at);

            $detectedExceptions[] = [
                'shipment' => $shipment,
                'type' => 'stuck_in_workflow',
                'severity' => 'medium',
                'notes' => "Shipment has not been updated for {$hoursStuck} hours",
                'auto_detected' => true,
            ];
        }

        // Detect unassigned urgent shipments
        $unassignedUrgent = Shipment::whereNull('assigned_worker_id')
            ->where(function ($query) {
                $query->where('priority', '>=', 3)
                      ->orWhere('expected_delivery_date', '<=', now()->addHours(24))
                      ->orWhere('service_level', 'express');
            })
            ->where('has_exception', false)
            ->whereNotIn('current_status', ['delivered', 'cancelled'])
            ->get();

        foreach ($unassignedUrgent as $shipment) {
            $detectedExceptions[] = [
                'shipment' => $shipment,
                'type' => 'unassigned_urgent',
                'severity' => 'high',
                'notes' => 'Urgent shipment has not been assigned to a worker',
                'auto_detected' => true,
            ];
        }

        return $detectedExceptions;
        */
    }

    /**
     * Bulk create exceptions from detection results
     * Note: Exception columns don't exist yet, returning mock response
     */
    public function bulkCreateExceptions(array $detectedExceptions): array
    {
        Log::info('ExceptionTowerService: Exception columns not yet implemented');
        return [
            'total' => 0,
            'created' => 0,
            'errors' => [],
        ];
        
        /* Original code - will be enabled when exception columns are added
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($detectedExceptions as $detected) {
            try {
                $result = $this->createException($detected['shipment'], [
                    'type' => $detected['type'],
                    'severity' => $detected['severity'],
                    'notes' => $detected['notes'],
                    'auto_detected' => true,
                ]);

                if ($result['success']) {
                    $results['created']++;
                } else {
                    $results['errors'][] = [
                        'shipment_id' => $detected['shipment']->id,
                        'error' => $result['message'],
                    ];
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'shipment_id' => $detected['shipment']->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
        */
    }

    /**
     * Format exception for API response
     * Note: Returning basic shipment data since exception columns don't exist
     */
    private function formatException(Shipment $shipment): array
    {
        // Return basic shipment info since exception columns don't exist
        return [
            'id' => $shipment->id,
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number ?? 'N/A',
            'customer_name' => $shipment->customer->name ?? 'Unknown',
            'origin_branch' => $shipment->originBranch->name ?? 'Unknown',
            'destination_branch' => $shipment->destBranch->name ?? 'Unknown',
            'current_status' => $shipment->current_status,
            'exception_type' => 'N/A',
            'severity' => 'N/A',
            'priority' => 0,
            'status' => 'N/A',
            'notes' => null,
            'occurred_at' => null,
            'assigned_to' => null,
            'assigned_at' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
            'age_hours' => 0,
            'is_overdue' => false,
        ];
    }

    /**
     * Determine exception severity
     */
    private function determineSeverity(Shipment $shipment, array $data): string
    {
        // Check if severity is explicitly provided
        if (isset($data['severity'])) {
            return $data['severity'];
        }

        $type = $data['type'] ?? 'general';

        // Determine severity based on type and shipment characteristics
        switch ($type) {
            case 'damaged':
            case 'lost':
            case 'stolen':
                return 'high';

            case 'address_issue':
            case 'wrong_address':
                return $shipment->priority >= 3 ? 'high' : 'medium';

            case 'delayed_delivery':
                $daysDelayed = now()->diffInDays($shipment->expected_delivery_date ?? now());
                return $daysDelayed > 3 ? 'high' : 'medium';

            case 'customs_hold':
            case 'compliance_issue':
                return 'high';

            default:
                return 'medium';
        }
    }

    /**
     * Calculate exception priority (1-5 scale)
     */
    private function calculatePriority(string $severity, Shipment $shipment): int
    {
        $basePriority = match($severity) {
            'high' => 4,
            'medium' => 3,
            'low' => 2,
            default => 3,
        };

        // Increase priority for high-value or urgent shipments
        if ($shipment->priority >= 3) {
            $basePriority = min(5, $basePriority + 1);
        }

        // Increase priority for VIP customers
        if ($shipment->customer && $shipment->customer->is_vip) {
            $basePriority = min(5, $basePriority + 1);
        }

        return $basePriority;
    }

    /**
     * Check if exception is overdue for resolution
     */
    private function isExceptionOverdue(Shipment $shipment): bool
    {
        if (!$shipment->exception_occurred_at) {
            return false;
        }

        $maxResolutionHours = match($shipment->exception_severity) {
            'high' => 24,    // 24 hours for high priority
            'medium' => 72,  // 3 days for medium priority
            'low' => 168,    // 1 week for low priority
            default => 72,
        };

        return now()->diffInHours($shipment->exception_occurred_at) > $maxResolutionHours;
    }

    /**
     * Send exception alert
     */
    private function sendExceptionAlert(Shipment $shipment, array $exception): void
    {
        $alertData = [
            'type' => 'exception.created',
            'severity' => 'high',
            'title' => 'High Priority Exception Created',
            'message' => "Exception: {$exception['exception_type']} for shipment {$exception['tracking_number']}",
            'data' => $exception,
            'recipients' => $this->getExceptionAlertRecipients($shipment),
        ];

        broadcast(new OperationalAlertEvent($alertData))->toOthers();
    }

    /**
     * Get recipients for exception alerts
     */
    private function getExceptionAlertRecipients(Shipment $shipment): array
    {
        $recipients = [];

        // Branch managers
        if ($shipment->originBranch) {
            $managers = $shipment->originBranch->branchManager()->with('user')->get();
            foreach ($managers as $manager) {
                $recipients[] = $manager->user->id;
            }
        }

        if ($shipment->destBranch && $shipment->destBranch->id !== $shipment->originBranch->id) {
            $managers = $shipment->destBranch->branchManager()->with('user')->get();
            foreach ($managers as $manager) {
                $recipients[] = $manager->user->id;
            }
        }

        // Supervisors and operations managers
        $supervisors = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['supervisor', 'operations_manager']);
        })->pluck('id');

        $recipients = array_merge($recipients, $supervisors->toArray());

        return array_unique($recipients);
    }

    /**
     * Calculate exception trends
     */
    private function calculateExceptionTrends(Collection $exceptions, Carbon $startDate, Carbon $endDate): array
    {
        $days = $startDate->diffInDays($endDate) + 1;
        $trends = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayExceptions = $exceptions->filter(function ($exception) use ($date) {
                return $exception->exception_occurred_at &&
                       $exception->exception_occurred_at->toDateString() === $date->toDateString();
            });

            $trends[] = [
                'date' => $date->toDateString(),
                'count' => $dayExceptions->count(),
                'by_severity' => $dayExceptions->groupBy('exception_severity')->map->count(),
            ];
        }

        return $trends;
    }
}