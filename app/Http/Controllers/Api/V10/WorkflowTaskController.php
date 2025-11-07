<?php

namespace App\Http\Controllers\Api\V10;

use App\Events\WorkflowTaskBroadcast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V10\StoreWorkflowTaskRequest;
use App\Http\Requests\Api\V10\UpdateWorkflowTaskRequest;
use App\Http\Requests\Api\V10\WorkflowTaskAssignRequest;
use App\Http\Requests\Api\V10\WorkflowTaskBulkDeleteRequest;
use App\Http\Requests\Api\V10\WorkflowTaskBulkUpdateRequest;
use App\Http\Requests\Api\V10\WorkflowTaskCommentRequest;
use App\Http\Requests\Api\V10\WorkflowTaskStatusRequest;
use App\Http\Resources\WorkflowItemResource;
use App\Http\Resources\WorkflowTaskActivityResource;
use App\Http\Resources\WorkflowTaskCommentResource;
use App\Models\WorkflowTask;
use App\Models\WorkflowTaskActivity;
use App\Models\WorkflowTaskComment;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowTaskController extends Controller
{
    use ApiReturnFormatTrait;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkflowTask::class);

        $query = WorkflowTask::query()->withSummary()->orderByDesc('last_status_at')->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $statuses = Arr::wrap($request->input('status'));
            $statuses = collect($statuses)
                ->flatMap(fn ($value) => is_string($value) ? explode(',', $value) : $value)
                ->map(fn ($value) => strtolower(trim((string) $value)))
                ->filter()
                ->values();

            if ($statuses->isNotEmpty()) {
                $query->whereIn('status', $statuses);
            }
        }

        if ($request->filled('priority')) {
            $priorities = Arr::wrap($request->input('priority'));
            $priorities = collect($priorities)
                ->flatMap(fn ($value) => is_string($value) ? explode(',', $value) : $value)
                ->map(fn ($value) => strtolower(trim((string) $value)))
                ->filter()
                ->values();

            if ($priorities->isNotEmpty()) {
                $query->whereIn('priority', $priorities);
            }
        }

        if ($request->filled('assigned_to')) {
            $assigned = Arr::wrap($request->input('assigned_to'));
            $assigned = collect($assigned)->map(fn ($value) => (int) $value)->filter()->values();
            if ($assigned->isNotEmpty()) {
                $query->whereIn('assigned_to', $assigned);
            }
        }

        if ($request->boolean('unassigned_only')) {
            $query->whereNull('assigned_to');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tag')) {
            $tags = Arr::wrap($request->input('tag'));
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if (! $request->boolean('include_completed')) {
            $query->where('status', '!=', 'completed');
        }

        $limit = (int) $request->input('limit', 200);
        $limit = max(1, min($limit, 500));
        $tasks = $query->limit($limit)->get();

        $summary = $this->buildSummary();

        return $this->responseWithSuccess('Workflow tasks loaded', [
            'tasks' => WorkflowItemResource::collection($tasks)->toArray($this->safeRequest()),
            'summary' => $summary->toArray(),
            'meta' => [
                'count' => $tasks->count(),
                'limit' => $limit,
                'refreshed_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function store(StoreWorkflowTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $task = DB::transaction(function () use ($validated, $user) {
            $data = $validated;
            $data['creator_id'] = $user?->id;
            $data['last_status_at'] = now();

            if (isset($data['due_at'])) {
                $data['due_at'] = Carbon::parse($data['due_at']);
            }

            $task = new WorkflowTask($data);
            $task->save();

            $this->recordActivity($task, $user?->id, 'created', [
                'title' => $task->title,
                'status' => $task->status,
            ]);

            return $task;
        });

        $summary = $this->buildSummary();
        $this->broadcastWorkflowEvent('created', (new WorkflowItemResource($task))->toArray($this->safeRequest()), $summary->toArray());

        return $this->responseWithSuccess('Workflow task created', [
            'task' => (new WorkflowItemResource($task))->toArray($this->safeRequest()),
            'summary' => $summary->toArray(),
        ], 201);
    }

    public function show(WorkflowTask $workflowTask): JsonResponse
    {
        $this->authorize('view', $workflowTask);

        $workflowTask->loadMissing(['assignee:id,name,email', 'creator:id,name,email']);

        return $this->responseWithSuccess('Workflow task detail', [
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
        ]);
    }

    public function update(UpdateWorkflowTaskRequest $request, WorkflowTask $workflowTask): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $workflowTask, $request) {
            $originalStatus = $workflowTask->status;

            if (isset($validated['due_at'])) {
                $validated['due_at'] = $validated['due_at'] ? Carbon::parse($validated['due_at']) : null;
            }

            if (array_key_exists('status', $validated) && $validated['status'] !== $workflowTask->status) {
                $workflowTask->last_status_at = now();
                if ($validated['status'] === 'completed') {
                    $workflowTask->completed_at = now();
                }
            }

            $workflowTask->fill($validated);
            $workflowTask->save();

            $this->recordActivity($workflowTask, $request->user()?->id, 'updated', [
                'changed' => array_keys($validated),
                'previous_status' => $originalStatus,
                'status' => $workflowTask->status,
            ]);
        });

        $workflowTask->refresh()->loadMissing(['assignee:id,name,email', 'creator:id,name,email']);
        $summary = $this->buildSummary();

        $this->broadcastWorkflowEvent('updated', (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()), $summary->toArray());

        return $this->responseWithSuccess('Workflow task updated', [
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
            'summary' => $summary->toArray(),
        ]);
    }

    public function destroy(Request $request, WorkflowTask $workflowTask): JsonResponse
    {
        $this->authorize('delete', $workflowTask);

        DB::transaction(function () use ($workflowTask, $request) {
            $this->recordActivity($workflowTask, $request->user()?->id, 'deleted', [
                'title' => $workflowTask->title,
            ]);

            $workflowTask->delete();
        });

        $summary = $this->buildSummary();
        $this->broadcastWorkflowEvent('deleted', ['id' => (string) $workflowTask->id], $summary->toArray());

        return $this->responseWithSuccess('Workflow task deleted', [
            'summary' => $summary->toArray(),
        ]);
    }

    public function updateStatus(WorkflowTaskStatusRequest $request, WorkflowTask $workflowTask): JsonResponse
    {
        $data = $request->validated();
        $previous = $workflowTask->status;

        DB::transaction(function () use ($workflowTask, $data, $request) {
            $workflowTask->status = $data['status'];
            $workflowTask->last_status_at = now();
            $workflowTask->completed_at = $data['status'] === 'completed' ? now() : null;
            $workflowTask->save();

            $this->recordActivity($workflowTask, $request->user()?->id, 'status_changed', [
                'from' => $previous,
                'to' => $workflowTask->status,
                'note' => $data['note'] ?? null,
            ]);
        });

        $workflowTask->refresh()->loadMissing(['assignee:id,name,email', 'creator:id,name,email']);
        $summary = $this->buildSummary();

        $this->broadcastWorkflowEvent('status_changed', (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()), $summary->toArray());

        return $this->responseWithSuccess('Workflow task status updated', [
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
            'summary' => $summary->toArray(),
        ]);
    }

    public function assign(WorkflowTaskAssignRequest $request, WorkflowTask $workflowTask): JsonResponse
    {
        $data = $request->validated();
        $previous = $workflowTask->assigned_to;

        DB::transaction(function () use ($workflowTask, $data, $request, $previous) {
            $workflowTask->assigned_to = $data['assigned_to'];
            $workflowTask->save();

            $this->recordActivity($workflowTask, $request->user()?->id, 'assigned', [
                'from' => $previous,
                'to' => $workflowTask->assigned_to,
            ]);
        });

        $workflowTask->refresh()->loadMissing(['assignee:id,name,email', 'creator:id,name,email']);
        $summary = $this->buildSummary();

        $this->broadcastWorkflowEvent('assigned', (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()), $summary->toArray());

        return $this->responseWithSuccess('Workflow task assigned', [
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
            'summary' => $summary->toArray(),
        ]);
    }

    public function bulkUpdate(WorkflowTaskBulkUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $changes = $data['data'];
        $ids = collect($data['ids']);

        DB::transaction(function () use ($ids, $changes, $request) {
            $now = now();

            WorkflowTask::whereIn('id', $ids)->get()->each(function (WorkflowTask $task) use ($changes, $request, $now) {
                $originalStatus = $task->status;
                $task->fill($changes);
                if (array_key_exists('status', $changes) && $changes['status'] !== $originalStatus) {
                    $task->last_status_at = $now;
                    $task->completed_at = $changes['status'] === 'completed' ? $now : null;
                }
                $task->save();

                $this->recordActivity($task, $request->user()?->id, 'bulk_updated', [
                    'changes' => array_keys($changes),
                    'status_from' => $originalStatus,
                    'status_to' => $task->status,
                ]);
            });
        });

        $summary = $this->buildSummary();

        $this->broadcastWorkflowEvent('bulk_updated', [
            'ids' => $ids->map(fn ($id) => (string) $id)->values()->all(),
            'changes' => $changes,
        ], $summary->toArray());

        return $this->responseWithSuccess('Workflow tasks updated', [
            'summary' => $summary->toArray(),
        ]);
    }

    public function bulkDelete(WorkflowTaskBulkDeleteRequest $request): JsonResponse
    {
        $ids = collect($request->validated()['ids']);

        DB::transaction(function () use ($ids, $request) {
            WorkflowTask::whereIn('id', $ids)->get()->each(function (WorkflowTask $task) use ($request) {
                $this->recordActivity($task, $request->user()?->id, 'bulk_deleted', [
                    'title' => $task->title,
                ]);

                $task->delete();
            });
        });

        $summary = $this->buildSummary();

        $this->broadcastWorkflowEvent('bulk_deleted', [
            'ids' => $ids->map(fn ($id) => (string) $id)->values()->all(),
        ], $summary->toArray());

        return $this->responseWithSuccess('Workflow tasks deleted', [
            'summary' => $summary->toArray(),
        ]);
    }

    public function comments(WorkflowTaskCommentRequest $request, WorkflowTask $workflowTask): JsonResponse
    {
        $data = $request->validated();

        $comment = DB::transaction(function () use ($workflowTask, $request, $data) {
            $comment = new WorkflowTaskComment([
                'body' => $data['body'],
                'metadata' => $data['metadata'] ?? null,
            ]);
            $comment->user_id = $request->user()?->id;
            $workflowTask->comments()->save($comment);

            $this->recordActivity($workflowTask, $request->user()?->id, 'commented', [
                'comment_id' => $comment->id,
            ]);

            return $comment;
        });

        $workflowTask->loadCount(['comments', 'activities']);
        $summary = $this->buildSummary();

        $this->broadcastWorkflowEvent('commented', [
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
            'comment' => (new WorkflowTaskCommentResource($comment))->toArray($this->safeRequest()),
        ], $summary->toArray());

        return $this->responseWithSuccess('Comment added', [
            'comment' => (new WorkflowTaskCommentResource($comment))->toArray($this->safeRequest()),
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
            'summary' => $summary->toArray(),
        ], 201);
    }

    public function updateComment(WorkflowTaskCommentRequest $request, WorkflowTask $workflowTask, WorkflowTaskComment $comment): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($comment, $workflowTask, $request, $data) {
            $comment->fill([
                'body' => $data['body'],
                'metadata' => $data['metadata'] ?? null,
            ]);
            $comment->save();

            $this->recordActivity($workflowTask, $request->user()?->id, 'comment_updated', [
                'comment_id' => $comment->id,
            ]);
        });

        $workflowTask->loadCount(['comments', 'activities']);
        $summary = $this->buildSummary();

        $this->broadcastWorkflowEvent('comment_updated', [
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
            'comment' => (new WorkflowTaskCommentResource($comment))->toArray($this->safeRequest()),
        ], $summary->toArray());

        return $this->responseWithSuccess('Comment updated', [
            'comment' => (new WorkflowTaskCommentResource($comment))->toArray($this->safeRequest()),
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
            'summary' => $summary->toArray(),
        ]);
    }

    public function deleteComment(WorkflowTaskCommentRequest $request, WorkflowTask $workflowTask, WorkflowTaskComment $comment): JsonResponse
    {
        DB::transaction(function () use ($workflowTask, $comment, $request) {
            $this->recordActivity($workflowTask, $request->user()?->id, 'comment_deleted', [
                'comment_id' => $comment->id,
            ]);

            $comment->delete();
        });

        $workflowTask->loadCount(['comments', 'activities']);
        $summary = $this->buildSummary();

        $this->broadcastWorkflowEvent('comment_deleted', [
            'task' => (new WorkflowItemResource($workflowTask))->toArray($this->safeRequest()),
            'comment_id' => (string) $comment->id,
        ], $summary->toArray());

        return $this->responseWithSuccess('Comment removed', [
            'summary' => $summary->toArray(),
        ]);
    }

    public function history(WorkflowTask $workflowTask): JsonResponse
    {
        $this->authorize('view', $workflowTask);

        $activities = $workflowTask->activities()
            ->with('actor:id,name,email')
            ->latest('created_at')
            ->limit(50)
            ->get();

        return $this->responseWithSuccess('Workflow task history', [
            'history' => WorkflowTaskActivityResource::collection($activities)->toArray($this->safeRequest()),
        ]);
    }

    public function summary(): JsonResponse
    {
        $this->authorize('viewAny', WorkflowTask::class);

        return $this->responseWithSuccess('Workflow summary', [
            'summary' => $this->buildSummary()->toArray(),
        ]);
    }

    protected function recordActivity(WorkflowTask $task, ?int $userId, string $action, array $details = []): void
    {
        WorkflowTaskActivity::create([
            'workflow_task_id' => $task->id,
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    protected function broadcastWorkflowEvent(string $event, mixed $data, array $summary = []): void
    {
        try {
            WorkflowTaskBroadcast::dispatch($event, $data, $summary);
        } catch (\Throwable $e) {
            \Log::warning("Failed to broadcast workflow event: $event", ['error' => $e->getMessage()]);
        }
    }

    protected function safeRequest(): Request
    {
        $request = request();

        if ($request instanceof Request) {
            return $request;
        }

        return Request::create('/', 'GET');
    }

    protected function buildSummary(): Collection
    {
        $statusCounts = WorkflowTask::select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = collect([
            'total' => WorkflowTask::count(),
        ]);

        foreach (WorkflowTask::STATUSES as $status) {
            $summary[$status] = (int) ($statusCounts[$status] ?? 0);
        }

        return $summary;
    }
}
