<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransportLegSeeder extends Seeder
{
    public function run(): void
    {
        $shipments = DB::table('shipments')->limit(5)->get();
        foreach ($shipments as $s) {
            DB::table('transport_legs')->insert([
                'shipment_id' => $s->id,
                'mode' => 'AIR',
                'carrier' => 'KQ',
                'awb' => '123-'.str_pad((string)rand(10000000,99999999), 8, '0', STR_PAD_LEFT),
                'status' => 'PLANNED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

