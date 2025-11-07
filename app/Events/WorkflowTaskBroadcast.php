<?php

namespace App\Events;

use App\Http\Resources\WorkflowItemResource;
use App\Models\WorkflowTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WorkflowTaskBroadcast implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $action,
        public array $payload,
        public array $summary,
    ) {
    }

    public static function fromTask(WorkflowTask $task, string $action = 'updated', ?Collection $summary = null): self
    {
        $resource = new WorkflowItemResource($task);
        $request = request();

        if (! $request instanceof \Illuminate\Http\Request) {
            $request = new \Illuminate\Http\Request();
        }

        return new self(
            $action,
            $resource->toArray($request),
            $summary ? $summary->toArray() : [],
        );
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('workflow-tasks');
    }

    public function broadcastAs(): string
    {
        return 'workflow-task.' . $this->action;
    }
}
