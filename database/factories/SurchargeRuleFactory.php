<?php

namespace Database\Factories;

use App\Models\SurchargeRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurchargeRuleFactory extends Factory
{
    protected $model = SurchargeRule::class;

    public function definition(): array
    {
        $triggers = ['fuel','security','remote_area','oversize','weekend','dg','re_attempt','custom'];
        $rateType = $this->faker->randomElement(['flat','percent']);
        return [
            'code' => strtoupper($this->faker->bothify('SRG-###')),
            'name' => ucfirst($this->faker->randomElement($triggers)).' Surcharge',
            'trigger' => $this->faker->randomElement($triggers),
            'rate_type' => $rateType,
            'amount' => $rateType === 'flat' ? $this->faker->randomFloat(2, 1, 50) : $this->faker->randomFloat(1, 1, 20),
            'currency' => 'USD',
            'active_from' => now()->toDateString(),
            'active' => true,
        ];
    }
}

