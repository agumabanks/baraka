<?php

namespace Database\Seeders;

use App\Models\AwbStock;
use App\Models\Backend\Hub;
use Illuminate\Database\Seeder;

class AwbStockSeeder extends Seeder
{
    public function run(): void
    {
        $hubId = Hub::query()->value('id');
        if ($hubId) {
            AwbStock::firstOrCreate([
                'carrier_code' => 'TK', 'iata_prefix' => '235', 'range_start' => 23500000000, 'range_end' => 23500000100,
            ], [
                'hub_id' => $hubId, 'status' => 'active', 'used_count' => 0, 'voided_count' => 0,
            ]);
        }
    }
}
