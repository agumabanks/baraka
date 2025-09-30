<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\ShipmentStatus;
use App\Models\Backend\Hub;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'customer_id' => User::factory()->create(['user_type' => \App\Enums\UserType::MERCHANT]),
            'origin_branch_id' => Hub::factory(),
            'dest_branch_id' => Hub::factory(),
            'service_level' => $this->faker->randomElement(['standard', 'express', 'premium']),
            'incoterm' => $this->faker->randomElement(['DDP', 'DAP', 'FOB']),
            'price_amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => Currency::USD,
            'current_status' => $this->faker->randomElement(ShipmentStatus::cases()),
            'created_by' => User::factory()->create(['user_type' => \App\Enums\UserType::ADMIN]),
            'metadata' => ['test' => true],
        ];
    }
}
