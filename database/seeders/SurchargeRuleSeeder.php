<?php

namespace Database\Seeders;

use App\Models\SurchargeRule;
use Illuminate\Database\Seeder;

class SurchargeRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (SurchargeRule::count() > 0) return;

        SurchargeRule::factory()->count(3)->create();
    }
}

