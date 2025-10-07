<?php

namespace Database\Factories;

use App\Models\Backend\Branch;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'primary_branch_id' => Branch::factory(),
            'business_name' => $this->faker->company(),
            'status' => 'active',
            'kyc_data' => [
                'tax_id' => strtoupper($this->faker->bothify('TIN-#######')),
                'contact_name' => $this->faker->name(),
                'contact_phone' => $this->faker->phoneNumber(),
            ],
        ];
    }
}
