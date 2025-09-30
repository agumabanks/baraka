<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'platform' => $this->faker->randomElement(['ios', 'android', 'web']),
            'device_uuid' => $this->faker->uuid(),
            'push_token' => $this->faker->optional()->uuid(),
            'last_seen_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
