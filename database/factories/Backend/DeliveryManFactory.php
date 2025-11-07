<?php

namespace Database\Factories\Backend;

use App\Enums\Status;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryMan>
 */
class DeliveryManFactory extends Factory
{
    protected $model = DeliveryMan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'hub_id' => Hub::factory(),
            'status' => Status::ACTIVE,
            'delivery_charge' => $this->faker->randomFloat(2, 1, 10),
            'pickup_charge' => $this->faker->randomFloat(2, 1, 10),
            'return_charge' => $this->faker->randomFloat(2, 1, 10),
            'opening_balance' => 0,
            'current_balance' => $this->faker->randomFloat(2, 0, 100),
            'current_location_lat' => $this->faker->latitude(),
            'current_location_long' => $this->faker->longitude(),
        ];
    }
}
