<?php

namespace App\Events;

use App\Models\Backend\BranchWorker;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkerCapacityAlertEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BranchWorker $worker;
    public array $capacityData;

    /**
     * Create a new event instance.
     */
    public function __construct(BranchWorker $worker, array $capacityData)
    {
        $this->worker = $worker;
        $this->capacityData = $capacityData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('operations.alerts'),
        ];

        // Add branch-specific channel
        if ($this->worker->branch_id) {
            $channels[] = new Channel("operations.alerts.branch.{$this->worker->branch_id}");
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'alert.worker_overloaded',
            'title' => 'Worker Capacity Alert',
            'message' => "Worker {$this->worker->full_name} is overloaded",
            'severity' => $this->capacityData['utilization_rate'] > 95 ? 'critical' : 'high',
            'data' => [
                'worker_id' => $this->worker->id,
                'worker_name' => $this->worker->full_name,
                'branch_name' => $this->worker->branch->name ?? 'Unknown',
                'current_load' => $this->capacityData['current_load'],
                'capacity' => $this->capacityData['capacity'],
                'utilization_rate' => $this->capacityData['utilization_rate'],
            ],
            'action_required' => true,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'worker.capacity.alert';
    }
}