<?php

namespace Database\Seeders;

use App\Models\Lane;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZonesAndLanesSeeder extends Seeder
{
    public function run(): void
    {
        $eu = Zone::firstOrCreate(['code' => 'EU'], ['name' => 'European Union', 'countries' => ['DE', 'FR', 'NL', 'BE', 'IT', 'ES']]);
        $drc = Zone::firstOrCreate(['code' => 'DRC'], ['name' => 'DR Congo', 'countries' => ['CD']]);
        $uga = Zone::firstOrCreate(['code' => 'UG'], ['name' => 'Uganda', 'countries' => ['UG']]);

        Lane::firstOrCreate([
            'origin_zone_id' => $eu->id,
            'dest_zone_id' => $drc->id,
            'mode' => 'air',
        ], ['std_transit_days' => 5, 'dim_divisor' => 6000, 'eawb_required' => true]);

        Lane::firstOrCreate([
            'origin_zone_id' => $eu->id,
            'dest_zone_id' => $uga->id,
            'mode' => 'air',
        ], ['std_transit_days' => 4, 'dim_divisor' => 6000, 'eawb_required' => true]);
    }
}
