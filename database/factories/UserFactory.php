<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $payload = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
            'user_type' => $this->faker->randomElement([\App\Enums\UserType::MERCHANT, \App\Enums\UserType::DELIVERYMAN, \App\Enums\UserType::ADMIN]),
        ];

        if (Schema::hasColumn('users', 'preferred_language')) {
            $payload['preferred_language'] = $this->faker->randomElement(['en', 'fr', 'sw']);
        }

        if (Schema::hasColumn('users', 'primary_branch_id')) {
            $payload['primary_branch_id'] = null;
        }

        return $payload;
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
