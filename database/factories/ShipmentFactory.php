<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Backend\Branch;
use App\Models\Client;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'customer_id' => User::factory()->state([
                'user_type' => UserType::MERCHANT,
            ]),
            'origin_branch_id' => Branch::factory()->state([
                'type' => 'HUB',
                'is_hub' => true,
                'parent_branch_id' => null,
            ]),
            'dest_branch_id' => Branch::factory()->state([
                'type' => 'REGIONAL',
                'is_hub' => false,
            ]),
            'tracking_number' => Str::upper(Str::random(12)),
            'status' => 'created',
            'service_level' => $this->faker->randomElement(['standard', 'express', 'premium']),
            'incoterm' => $this->faker->randomElement(['DDP', 'DAP', 'FOB']),
            'price_amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'current_status' => $this->faker->randomElement(array_map(fn (ShipmentStatus $status) => $status->value, ShipmentStatus::cases())),
            'created_by' => User::factory()->state([
                'user_type' => UserType::ADMIN,
            ]),
            'metadata' => ['test' => true],
        ];
    }
}
