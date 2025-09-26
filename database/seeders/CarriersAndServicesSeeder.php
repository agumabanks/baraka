<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Carrier;
use App\Models\CarrierService;

class CarriersAndServicesSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            ['name' => 'DHL', 'code' => 'DHL', 'mode' => 'air', 'services' => [
                ['code' => 'EXP', 'name' => 'Express Worldwide', 'requires_eawb' => true],
                ['code' => 'ECON', 'name' => 'Economy Select', 'requires_eawb' => false],
            ]],
            ['name' => 'KQ Cargo', 'code' => 'KQ', 'mode' => 'air', 'services' => [
                ['code' => 'GEN', 'name' => 'General Cargo', 'requires_eawb' => true],
            ]],
            ['name' => 'RoadLine', 'code' => 'RL', 'mode' => 'road', 'services' => [
                ['code' => 'FTL', 'name' => 'Full Truck Load', 'requires_eawb' => false],
            ]],
        ];

        foreach ($carriers as $c) {
            $carrier = Carrier::firstOrCreate(['code' => $c['code']], ['name' => $c['name'], 'mode' => $c['mode']]);
            foreach ($c['services'] as $s) {
                CarrierService::firstOrCreate([
                    'carrier_id' => $carrier->id,
                    'code' => $s['code'],
                ], [
                    'name' => $s['name'],
                    'requires_eawb' => $s['requires_eawb'],
                ]);
            }
        }
    }
}

