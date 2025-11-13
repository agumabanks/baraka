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

class CreateManualInterventionWorkflowTask implements ShouldQueue
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
            // Create manual intervention workflow task
            $task = WorkflowTask::create([
                'title' => "Manual Intervention for {$this->shipment->tracking_number}",
                'description' => $this->buildInterventionDescription(),
                'type' => 'manual_intervention',
                'priority' => 'high',
                'status' => 'open',
                'assignable_type' => Shipment::class,
                'assignable_id' => $this->shipment->id,
                'created_by' => $this->scanData['user_id'] ?? null,
                'due_date' => now()->addHours(1), // 1 hour for urgent intervention
                'metadata' => [
                    'scan_data' => $this->scanData,
                    'intervention_type' => $this->determineInterventionType(),
                    'auto_generated' => true,
                    'requires_approval' => true,
                ],
            ]);

            // Create escalation workflow
            $this->createEscalationWorkflow($task);

            // Notify supervisors
            $this->notifySupervisors($task);

            Log::info('Manual intervention workflow task created', [
                'shipment_id' => $this->shipment->id,
                'task_id' => $task->id,
                'tracking_number' => $this->shipment->tracking_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create manual intervention workflow task', [
                'shipment_id' => $this->shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build manual intervention description
     */
    private function buildInterventionDescription(): string
    {
        $description = "Manual intervention scan recorded for shipment {$this->shipment->tracking_number}\n\n";
        $description .= "Intervention Details:\n";
        $description .= "- Action: {$this->scanData['action']}\n";
        $description .= "- Location: {$this->scanData['location_id']}\n";
        $description .= "- Timestamp: {$this->scanData['timestamp']}\n";
        
        if (!empty($this->scanData['notes'])) {
            $description .= "- Notes: {$this->scanData['notes']}\n";
        }

        $description .= "\nRequired Actions:\n";
        $description .= "1. Review intervention reason\n";
        $description .= "2. Determine resolution path\n";
        $description .= "3. Assign appropriate handler\n";
        $description .= "4. Monitor progress\n";
        $description .= "5. Update shipment status\n";

        return $description;
    }

    /**
     * Determine intervention type based on scan data
     */
    private function determineInterventionType(): string
    {
        $notes = strtolower($this->scanData['notes'] ?? '');
        
        if (strpos($notes, 'address') !== false || strpos($notes, 'wrong') !== false) {
            return 'address_correction';
        }
        
        if (strpos($notes, 'customer') !== false || strpos($notes, 'unavailable') !== false) {
            return 'customer_unavailable';
        }
        
        if (strpos($notes, 'security') !== false || strpos($notes, 'access') !== false) {
            return 'security_issue';
        }
        
        if (strpos($notes, 'weather') !== false || strpos($notes, 'road') !== false) {
            return 'environmental';
        }
        
        return 'general_intervention';
    }

    /**
     * Create escalation workflow
     */
    private function createEscalationWorkflow(WorkflowTask $parentTask): void
    {
        $interventionType = $this->determineInterventionType();
        $escalationTasks = [];

        switch ($interventionType) {
            case 'address_correction':
                $escalationTasks = [
                    ['title' => 'Verify correct address', 'priority' => 'high', 'due_in_hours' => 0],
                    ['title' => 'Contact customer for confirmation', 'priority' => 'high', 'due_in_hours' => 0],
                    ['title' => 'Update shipment details', 'priority' => 'medium', 'due_in_hours' => 1],
                    ['title' => 'Re-route if necessary', 'priority' => 'medium', 'due_in_hours' => 2],
                ];
                break;

            case 'customer_unavailable':
                $escalationTasks = [
                    ['title' => 'Attempt delivery at alternate time', 'priority' => 'high', 'due_in_hours' => 1],
                    ['title' => 'Leave notice card', 'priority' => 'high', 'due_in_hours' => 0],
                    ['title' => 'Schedule redelivery', 'priority' => 'medium', 'due_in_hours' => 2],
                    ['title' => 'Contact customer service', 'priority' => 'medium', 'due_in_hours' => 1],
                ];
                break;

            case 'security_issue':
                $escalationTasks = [
                    ['title' => 'Ensure safety protocols', 'priority' => 'high', 'due_in_hours' => 0],
                    ['title' => 'Contact security team', 'priority' => 'high', 'due_in_hours' => 0],
                    ['title' => 'Document security concerns', 'priority' => 'high', 'due_in_hours' => 1],
                    ['title' => 'Seek alternative delivery method', 'priority' => 'medium', 'due_in_hours' => 2],
                ];
                break;

            default:
                $escalationTasks = [
                    ['title' => 'Assess situation', 'priority' => 'high', 'due_in_hours' => 0],
                    ['title' => 'Determine resolution path', 'priority' => 'high', 'due_in_hours' => 0],
                    ['title' => 'Execute resolution', 'priority' => 'medium', 'due_in_hours' => 1],
                    ['title' => 'Update shipment status', 'priority' => 'medium', 'due_in_hours' => 1],
                ];
        }

        foreach ($escalationTasks as $taskData) {
            WorkflowTask::create([
                'title' => $taskData['title'],
                'description' => "Escalation sub-task for: {$parentTask->title}",
                'type' => 'sub_task',
                'priority' => $taskData['priority'],
                'status' => 'open',
                'assignable_type' => WorkflowTask::class,
                'assignable_id' => $parentTask->id,
                'parent_task_id' => $parentTask->id,
                'due_date' => now()->addHours($taskData['due_in_hours']),
                'metadata' => [
                    'parent_task_id' => $parentTask->id,
                    'auto_generated' => true,
                    'escalation_level' => 1,
                ],
            ]);
        }
    }

    /**
     * Notify supervisors about manual intervention
     */
    private function notifySupervisors(WorkflowTask $task): void
    {
        // This would typically integrate with your notification system
        // For now, we'll just log the notification
        
        Log::info('Manual intervention requires supervisor attention', [
            'task_id' => $task->id,
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'intervention_type' => $this->determineInterventionType(),
        ]);

        // In a real implementation, you would:
        // 1. Send push notifications to supervisors
        // 2. Send emails to relevant staff
        // 3. Create dashboard alerts
        // 4. Trigger SMS notifications for critical issues
    }
}