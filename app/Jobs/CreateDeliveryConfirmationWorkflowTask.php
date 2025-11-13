<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Models\WorkflowTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateDeliveryConfirmationWorkflowTask implements ShouldQueue
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
            // Create delivery confirmation workflow task
            $task = WorkflowTask::create([
                'title' => "Delivery Confirmation for {$this->shipment->tracking_number}",
                'description' => $this->buildDeliveryDescription(),
                'type' => 'delivery_confirmation',
                'priority' => 'medium',
                'status' => 'open',
                'assignable_type' => Shipment::class,
                'assignable_id' => $this->shipment->id,
                'created_by' => $this->scanData['user_id'] ?? null,
                'due_date' => now()->addHours(4), // 4 hours to complete confirmation
                'metadata' => [
                    'scan_data' => $this->scanData,
                    'delivery_type' => $this->determineDeliveryType(),
                    'auto_generated' => true,
                    'requires_customer_feedback' => true,
                ],
            ]);

            // Schedule automated tasks
            $this->scheduleAutomatedTasks($task);

            // Create customer communication task
            $this->createCustomerCommunicationTask($task);

            Log::info('Delivery confirmation workflow task created', [
                'shipment_id' => $this->shipment->id,
                'task_id' => $task->id,
                'tracking_number' => $this->shipment->tracking_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create delivery confirmation workflow task', [
                'shipment_id' => $this->shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build delivery confirmation description
     */
    private function buildDeliveryDescription(): string
    {
        $description = "Delivery confirmation scan recorded for shipment {$this->shipment->tracking_number}\n\n";
        $description .= "Delivery Details:\n";
        $description .= "- Action: {$this->scanData['action']}\n";
        $description .= "- Location: {$this->scanData['location_id']}\n";
        $description .= "- Timestamp: {$this->scanData['timestamp']}\n";
        
        // Safely get customer name
        $customerName = 'N/A';
        if ($this->shipment->customer && property_exists($this->shipment->customer, 'name')) {
            $customerName = $this->shipment->customer->name;
        }
        $description .= "- Customer: {$customerName}\n";
        
        if (!empty($this->scanData['notes'])) {
            $description .= "- Delivery Notes: {$this->scanData['notes']}\n";
        }

        $description .= "\nRequired Actions:\n";
        $description .= "1. Send delivery confirmation to customer\n";
        $description .= "2. Update customer portal/status\n";
        $description .= "3. Collect customer feedback\n";
        $description .= "4. Process any COD payments\n";
        $description .= "5. Update delivery statistics\n";

        return $description;
    }

    /**
     * Determine delivery type
     */
    private function determineDeliveryType(): string
    {
        if (!empty($this->scanData['notes'])) {
            $notes = strtolower($this->scanData['notes']);
            
            if (strpos($notes, 'signature') !== false) {
                return 'signature_required';
            }
            
            if (strpos($notes, 'cod') !== false || strpos($notes, 'cash') !== false) {
                return 'cod_delivery';
            }
            
            if (strpos($notes, 'leave') !== false) {
                return 'leave_at_door';
            }
        }

        return 'standard_delivery';
    }

    /**
     * Schedule automated tasks
     */
    private function scheduleAutomatedTasks(WorkflowTask $parentTask): void
    {
        // Schedule email confirmation (immediate)
        dispatch(new \App\Jobs\SendDeliveryConfirmationEmail($this->shipment));
        
        // Schedule SMS confirmation (immediate)
        if ($this->shipment->customer && property_exists($this->shipment->customer, 'phone') && $this->shipment->customer->phone) {
            dispatch(new \App\Jobs\SendDeliveryConfirmationSms($this->shipment));
        }

        // Schedule customer feedback request (after 1 hour)
        dispatch(new \App\Jobs\SendCustomerFeedbackRequest($this->shipment))
            ->delay(now()->addHour());

        // Schedule delivery analytics update (immediate)
        dispatch(new \App\Jobs\UpdateDeliveryAnalytics($this->shipment));
    }

    /**
     * Create customer communication task
     */
    private function createCustomerCommunicationTask(WorkflowTask $parentTask): void
    {
        $communicationTask = WorkflowTask::create([
            'title' => "Customer Communication for {$this->shipment->tracking_number}",
            'description' => "Handle customer communication and follow-up for delivery",
            'type' => 'customer_communication',
            'priority' => 'medium',
            'status' => 'open',
            'assignable_type' => WorkflowTask::class,
            'assignable_id' => $parentTask->id,
            'parent_task_id' => $parentTask->id,
            'due_date' => now()->addHours(2),
            'metadata' => [
                'parent_task_id' => $parentTask->id,
                'customer_id' => $this->getCustomerId(),
                'auto_generated' => true,
                'communication_type' => 'delivery_confirmation',
            ],
        ]);

        // Add sub-tasks for communication
        $this->createCommunicationSubTasks($communicationTask);
    }

    /**
     * Get customer ID safely
     */
    private function getCustomerId()
    {
        if ($this->shipment->customer && property_exists($this->shipment->customer, 'id')) {
            return $this->shipment->customer->id;
        }
        return null;
    }

    /**
     * Create communication sub-tasks
     */
    private function createCommunicationSubTasks(WorkflowTask $parentTask): void
    {
        $subTasks = [
            [
                'title' => 'Send delivery confirmation email',
                'priority' => 'high',
                'due_in_hours' => 0,
            ],
            [
                'title' => 'Send delivery confirmation SMS',
                'priority' => 'high',
                'due_in_hours' => 0,
                'condition' => 'has_phone',
            ],
            [
                'title' => 'Update customer portal',
                'priority' => 'medium',
                'due_in_hours' => 1,
            ],
            [
                'title' => 'Request customer feedback',
                'priority' => 'low',
                'due_in_hours' => 2,
            ],
            [
                'title' => 'Process COD payment if applicable',
                'priority' => 'high',
                'due_in_hours' => 1,
                'condition' => 'is_cod',
            ],
        ];

        foreach ($subTasks as $subTaskData) {
            // Check condition
            if (isset($subTaskData['condition'])) {
                $shouldSkip = false;
                
                if ($subTaskData['condition'] === 'has_phone') {
                    $hasPhone = $this->shipment->customer && 
                               property_exists($this->shipment->customer, 'phone') && 
                               $this->shipment->customer->phone;
                    $shouldSkip = !$hasPhone;
                }
                
                if ($subTaskData['condition'] === 'is_cod') {
                    $isCod = property_exists($this->shipment, 'payment_type') && 
                            $this->shipment->payment_type === 'cod';
                    $shouldSkip = !$isCod;
                }
                
                if ($shouldSkip) {
                    continue;
                }
            }

            WorkflowTask::create([
                'title' => $subTaskData['title'],
                'description' => "Communication sub-task for: {$parentTask->title}",
                'type' => 'sub_task',
                'priority' => $subTaskData['priority'],
                'status' => 'open',
                'assignable_type' => WorkflowTask::class,
                'assignable_id' => $parentTask->id,
                'parent_task_id' => $parentTask->id,
                'due_date' => now()->addHours($subTaskData['due_in_hours']),
                'metadata' => [
                    'parent_task_id' => $parentTask->id,
                    'auto_generated' => true,
                    'communication_type' => 'delivery_confirmation',
                ],
            ]);
        }
    }
}