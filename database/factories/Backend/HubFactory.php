<?php

namespace Database\Factories\Backend;

use App\Models\Backend\Hub;
use Illuminate\Database\Eloquent\Factories\Factory;

class HubFactory extends Factory
{
    protected $model = Hub::class;

    public function definition(): array
    {
        return [
            'name' => 'Hub '.$this->faker->city(),
            'address' => $this->faker->address(),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

