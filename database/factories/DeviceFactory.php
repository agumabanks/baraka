<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'device_id' => $this->faker->uuid(),
            'device_name' => $this->faker->words(2, true),
            'device_token' => Str::random(64),
            'app_version' => '1.' . $this->faker->numberBetween(0, 9) . '.' . $this->faker->numberBetween(0, 9),
            'fcm_token' => $this->faker->optional()->sha256(),
            'push_token' => $this->faker->optional()->uuid(),
            'last_seen_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'is_active' => true,
        ];
    }
}
