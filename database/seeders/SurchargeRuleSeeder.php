<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SurchargeRule;

class SurchargeRuleSeeder extends Seeder
{
    public function run(): void
    {
        SurchargeRule::updateOrCreate(['code'=>'FUEL'], [
            'name'=>'Fuel Surcharge','trigger'=>'fuel','rate_type'=>'percent','amount'=>12.5,
            'active_from'=>now()->subMonth(),'active'=>true
        ]);
        SurchargeRule::updateOrCreate(['code'=>'REMOTE'], [
            'name'=>'Remote Area','trigger'=>'remote_area','rate_type'=>'flat','amount'=>5.00,
            'currency'=>'USD','active_from'=>now()->subMonth(),'active'=>true
        ]);
        SurchargeRule::updateOrCreate(['code'=>'WEEKEND'], [
            'name'=>'Weekend Delivery','trigger'=>'weekend','rate_type'=>'flat','amount'=>2.00,
            'currency'=>'USD','active_from'=>now()->subMonth(),'active'=>true
        ]);
    }
}

