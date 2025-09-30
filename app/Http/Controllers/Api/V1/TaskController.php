<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Task;
use App\Models\Shipment;
use App\Models\DeliveryMan;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Tasks",
 *     description="API Endpoints for task management"
 * )
 */
class TaskController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/tasks",
     *     summary="List tasks for authenticated driver",
     *     description="Retrieve tasks assigned to the authenticated driver",
     *     operationId="getDriverTasks",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by task status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "assigned", "in_progress", "completed", "failed"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by task type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pickup", "delivery", "return"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tasks retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="tasks", type="array", @OA\Items(ref="#/components/schemas/Task"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not a driver"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $driver = DeliveryMan::where('user_id', auth()->id())->first();

        if (!$driver) {
            return $this->responseWithError('Not authorized as driver', [], 403);
        }

        $query = Task::where('driver_id', $driver->id)
            ->with(['shipment', 'shipment.customer']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $tasks = $query->orderBy('priority', 'desc')
                      ->orderBy('scheduled_at', 'asc')
                      ->paginate(20);

        return $this->responseWithSuccess('Tasks retrieved successfully', [
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tasks/{task}",
     *     summary="Get task details",
     *     description="Retrieve detailed information about a specific task",
     *     operationId="getTask",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="task", ref="#/components/schemas/Task")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not assigned to task"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Task not found"
     *     )
     * )
     */
    public function show(Task $task)
    {
        $driver = DeliveryMan::where('user_id', auth()->id())->first();

        if (!$driver || $task->driver_id !== $driver->id) {
            return $this->responseWithError('Not authorized for this task', [], 403);
        }

        $task->load(['shipment', 'shipment.customer', 'podProof']);

        return $this->responseWithSuccess('Task retrieved successfully', [
            'task' => new TaskResource($task),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/tasks/{task}/status",
     *     summary="Update task status",
     *     description="Update the status of a task (e.g., start, complete)",
     *     operationId="updateTaskStatus",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "assigned", "in_progress", "completed", "failed"}),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Task status updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not assigned to task"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function updateStatus(Request $request, Task $task)
    {
        $driver = DeliveryMan::where('user_id', auth()->id())->first();

        if (!$driver || $task->driver_id !== $driver->id) {
            return $this->responseWithError('Not authorized for this task', [], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,assigned,in_progress,completed,failed',
            'notes' => 'sometimes|string|max:500',
        ]);

        $task->update([
            'status' => $request->status,
            'completed_at' => $request->status === 'completed' ? now() : $task->completed_at,
            'metadata' => array_merge($task->metadata ?? [], [
                'status_updated_at' => now(),
                'status_notes' => $request->notes,
            ]),
        ]);

        return $this->responseWithSuccess('Task status updated successfully');
    }
}