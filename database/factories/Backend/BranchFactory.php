<?php

namespace Database\Factories\Backend;

use App\Enums\BranchType;
use App\Enums\Status;
use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $isHub = $this->faker->boolean(30);
        $type = $isHub
            ? BranchType::HUB->value
            : $this->faker->randomElement([
                BranchType::REGIONAL_BRANCH->value,
                BranchType::DESTINATION_BRANCH->value,
            ]);

        $latitude = $this->faker->latitude();
        $longitude = $this->faker->longitude();

        return [
            'name' => $this->faker->company.' Branch',
            'code' => strtoupper($this->faker->bothify('BR-###')),
            'type' => $type,
            'is_hub' => $isHub,
            'parent_branch_id' => null,
            'address' => $this->faker->streetAddress(),
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'geo_lat' => $latitude,
            'geo_lng' => $longitude,
            'time_zone' => $this->faker->timezone(),
            'capacity_parcels_per_day' => $this->faker->numberBetween(100, 1000),
            'operating_hours' => [
                1 => ['open' => '08:00', 'close' => '18:00'],
                2 => ['open' => '08:00', 'close' => '18:00'],
                3 => ['open' => '08:00', 'close' => '18:00'],
                4 => ['open' => '08:00', 'close' => '18:00'],
                5 => ['open' => '08:00', 'close' => '18:00'],
            ],
            'capabilities' => ['pickup', 'delivery'],
            'metadata' => [
                'capacity' => $this->faker->numberBetween(100, 1000),
            ],
            'status' => Status::ACTIVE,
        ];
    }
}
