<?php

namespace Tests\Feature\Api\V10;

use App\Models\User;
use App\Models\WorkflowTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class WorkflowTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateWithPermission(string $permission = 'workflow.manage'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['permissions' => [$permission]])->save();

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_index_returns_tasks(): void
    {
        $this->authenticateWithPermission();

        WorkflowTask::factory()->count(3)->create();

        $response = $this->getJson('/api/v10/workflow-items')
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->has('data.tasks', 3)
                ->has('data.summary.total')
            );

        $payload = $response->json('data.tasks');
        $this->assertIsArray($payload);
    }

    public function test_store_creates_task(): void
    {
        $user = $this->authenticateWithPermission();

        $payload = [
            'title' => 'New Task',
            'description' => 'Do the thing',
            'priority' => 'high',
            'status' => 'pending',
            'due_at' => now()->addDay()->toIso8601String(),
        ];

        $this->postJson('/api/v10/workflow-items', $payload)
            ->assertStatus(201)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->where('data.task.title', 'New Task')
                ->where('data.task.priority', 'high')
                ->where('data.task.assignedUserId', null)
            );

        $this->assertDatabaseHas('workflow_tasks', [
            'title' => 'New Task',
            'creator_id' => $user->id,
        ]);
    }

    public function test_status_update_requires_permission(): void
    {
        $user = $this->authenticateWithPermission();
        $task = WorkflowTask::factory()->create();

        $this->patchJson("/api/v10/workflow-items/{$task->id}/status", ['status' => 'in_progress'])
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->where('data.task.status', 'in_progress')
            );

        $this->assertDatabaseHas('workflow_tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_assign_updates_assignee(): void
    {
        $manager = $this->authenticateWithPermission();
        $assignee = User::factory()->create();
        $task = WorkflowTask::factory()->create();

        $this->patchJson("/api/v10/workflow-items/{$task->id}/assign", ['assigned_to' => $assignee->id])
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->where('data.task.assignedUserId', (string) $assignee->id)
            );

        $this->assertDatabaseHas('workflow_tasks', [
            'id' => $task->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_bulk_delete_removes_tasks(): void
    {
        $this->authenticateWithPermission();
        $tasks = WorkflowTask::factory()->count(2)->create();

        $this->postJson('/api/v10/workflow-items/bulk-delete', [
            'ids' => $tasks->pluck('id')->all(),
        ])->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
            );

        foreach ($tasks as $task) {
            $this->assertSoftDeleted('workflow_tasks', ['id' => $task->id]);
        }
    }
}
