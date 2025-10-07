<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Backend\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $isHub = $this->faker->boolean(30);
        $type = $isHub
            ? 'HUB'
            : $this->faker->randomElement(['REGIONAL', 'LOCAL']);

        return [
            'name' => $this->faker->company.' Branch',
            'code' => strtoupper($this->faker->bothify('BR-###')),
            'type' => $type,
            'is_hub' => $isHub,
            'parent_branch_id' => null,
            'address' => $this->faker->streetAddress(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'operating_hours' => [
                'open' => '08:00',
                'close' => '18:00',
            ],
            'capabilities' => ['pickup', 'delivery'],
            'metadata' => [
                'capacity' => $this->faker->numberBetween(100, 1000),
            ],
            'status' => Status::ACTIVE,
        ];
    }
}
