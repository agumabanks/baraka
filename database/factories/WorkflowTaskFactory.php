<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkflowTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowTask>
 */
class WorkflowTaskFactory extends Factory
{
    protected $model = WorkflowTask::class;

    public function definition(): array
    {
        $statuses = WorkflowTask::STATUSES;
        $status = $this->faker->randomElement($statuses);

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => $status,
            'priority' => $this->faker->randomElement(WorkflowTask::PRIORITIES),
            'creator_id' => User::factory(),
            'assigned_to' => null,
            'project_id' => null,
            'project_name' => $this->faker->optional()->company(),
            'stage' => $this->faker->optional()->word(),
            'status_label' => null,
            'client' => $this->faker->optional()->company(),
            'tracking_number' => $this->faker->optional()->numerify('TASK-#####'),
            'due_at' => $this->faker->optional()->dateTimeBetween('now', '+7 days'),
            'completed_at' => $status === 'completed' ? $this->faker->dateTimeBetween('-2 days', 'now') : null,
            'last_status_at' => now(),
            'tags' => $this->faker->randomElements(['finance', 'ops', 'support', 'priority'], rand(0, 3)),
            'metadata' => [
                'service_level' => $this->faker->optional()->randomElement(['Standard', 'Express']),
            ],
            'time_tracking' => [
                'total_seconds' => $this->faker->numberBetween(0, 28800),
                'running' => false,
            ],
            'dependencies' => [],
            'attachments' => [],
            'watchers' => [],
        ];
    }
}
