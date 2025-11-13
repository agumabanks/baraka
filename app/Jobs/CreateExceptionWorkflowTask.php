<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\WorkflowTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateExceptionWorkflowTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Shipment $shipment;
    public array $scanData;

    /**
     * Create a new job instance.
     */
    public function __construct(Shipment $shipment, array $scanData)
    {
        $this->shipment = $shipment;
        $this->scanData = $scanData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Create exception workflow task
            $task = WorkflowTask::create([
                'title' => "Exception for Shipment {$this->shipment->tracking_number}",
                'description' => $this->buildExceptionDescription(),
                'type' => 'exception_handling',
                'priority' => 'high',
                'status' => 'open',
                'assignable_type' => Shipment::class,
                'assignable_id' => $this->shipment->id,
                'created_by' => $this->scanData['user_id'] ?? null,
                'due_date' => now()->addHours(2), // 2 hours to resolve
                'metadata' => [
                    'scan_data' => $this->scanData,
                    'exception_type' => $this->determineExceptionType(),
                    'auto_generated' => true,
                ],
            ]);

            // Auto-assign based on scan location
            $this->autoAssignTask($task);

            // Create sub-tasks for common exception scenarios
            $this->createSubTasks($task);

            Log::info('Exception workflow task created', [
                'shipment_id' => $this->shipment->id,
                'task_id' => $task->id,
                'tracking_number' => $this->shipment->tracking_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create exception workflow task', [
                'shipment_id' => $this->shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build exception description
     */
    private function buildExceptionDescription(): string
    {
        $description = "Exception scan recorded for shipment {$this->shipment->tracking_number}\n\n";
        $description .= "Scan Details:\n";
        $description .= "- Action: {$this->scanData['action']}\n";
        $description .= "- Location: {$this->scanData['location_id']}\n";
        $description .= "- Timestamp: {$this->scanData['timestamp']}\n";
        
        if (!empty($this->scanData['notes'])) {
            $description .= "- Notes: {$this->scanData['notes']}\n";
        }

        if (!empty($this->scanData['latitude']) && !empty($this->scanData['longitude'])) {
            $description .= "- Location: {$this->scanData['latitude']}, {$this->scanData['longitude']}\n";
        }

        $description .= "\nRequired Actions:\n";
        $description .= "1. Investigate exception cause\n";
        $description .= "2. Contact customer if necessary\n";
        $description .= "3. Determine resolution path\n";
        $description .= "4. Update shipment status\n";
        $description .= "5. Document resolution\n";

        return $description;
    }

    /**
     * Determine exception type based on scan data
     */
    private function determineExceptionType(): string
    {
        // Analyze scan data to determine exception type
        $action = $this->scanData['action'] ?? '';
        $notes = strtolower($this->scanData['notes'] ?? '');

        if (strpos($notes, 'damaged') !== false || strpos($notes, 'broken') !== false) {
            return 'damaged_package';
        }

        if (strpos($notes, 'lost') !== false || strpos($notes, 'missing') !== false) {
            return 'lost_package';
        }

        if (strpos($notes, 'address') !== false) {
            return 'address_issue';
        }

        if (strpos($notes, 'customer') !== false) {
            return 'customer_issue';
        }

        return 'general_exception';
    }

    /**
     * Auto-assign task based on scan location
     */
    private function autoAssignTask(WorkflowTask $task): void
    {
        // Get branch staff who should handle exceptions
        $branch = Branch::find($this->scanData['location_id']);
        
        if ($branch) {
            // Auto-assign to branch supervisor or exception handler
            $assignee = $this->findBranchExceptionHandler($branch);
            
            if ($assignee) {
                $task->update([
                    'assigned_to' => $assignee->id,
                    'assigned_at' => now(),
                ]);
            }
        }
    }

    /**
     * Find branch exception handler
     */
    private function findBranchExceptionHandler(Branch $branch)
    {
        // Logic to find appropriate exception handler
        // This could be based on role, availability, or workload
        return $branch->workers()
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'exception_handler')
                      ->orWhere('name', 'supervisor')
                      ->orWhere('name', 'branch_manager');
            })
            ->first()?->user;
    }

    /**
     * Create sub-tasks for common exception scenarios
     */
    private function createSubTasks(WorkflowTask $parentTask): void
    {
        $exceptionType = $this->determineExceptionType();
        $subTasks = [];

        switch ($exceptionType) {
            case 'damaged_package':
                $subTasks = [
                    ['title' => 'Assess damage', 'priority' => 'high'],
                    ['title' => 'Take photos', 'priority' => 'medium'],
                    ['title' => 'Contact shipper', 'priority' => 'high'],
                    ['title' => 'Process insurance claim', 'priority' => 'medium'],
                ];
                break;

            case 'lost_package':
                $subTasks = [
                    ['title' => 'Check last known location', 'priority' => 'high'],
                    ['title' => 'Review security footage', 'priority' => 'medium'],
                    ['title' => 'Notify authorities if required', 'priority' => 'high'],
                    ['title' => 'Process customer compensation', 'priority' => 'high'],
                ];
                break;

            case 'address_issue':
                $subTasks = [
                    ['title' => 'Verify address details', 'priority' => 'high'],
                    ['title' => 'Contact customer for clarification', 'priority' => 'high'],
                    ['title' => 'Update shipment details', 'priority' => 'medium'],
                    ['title' => 'Re-route if necessary', 'priority' => 'medium'],
                ];
                break;

            default:
                $subTasks = [
                    ['title' => 'Investigate exception cause', 'priority' => 'high'],
                    ['title' => 'Determine resolution path', 'priority' => 'high'],
                    ['title' => 'Update customer', 'priority' => 'medium'],
                ];
        }

        foreach ($subTasks as $subTaskData) {
            WorkflowTask::create([
                'title' => $subTaskData['title'],
                'description' => "Sub-task for: {$parentTask->title}",
                'type' => 'sub_task',
                'priority' => $subTaskData['priority'],
                'status' => 'open',
                'assignable_type' => WorkflowTask::class,
                'assignable_id' => $parentTask->id,
                'parent_task_id' => $parentTask->id,
                'due_date' => now()->addHours(1),
                'metadata' => [
                    'parent_task_id' => $parentTask->id,
                    'auto_generated' => true,
                ],
            ]);
        }
    }
}