<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SortationBin;
use App\Models\WhLocation;
use App\Models\Backend\Hub;

class SortationAndWarehouseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Hub::all() as $hub) {
            SortationBin::firstOrCreate(['branch_id'=>$hub->id,'code'=>'BIN-A'], ['lane'=>'A','status'=>'active']);
            SortationBin::firstOrCreate(['branch_id'=>$hub->id,'code'=>'BIN-B'], ['lane'=>'B','status'=>'active']);

            WhLocation::firstOrCreate(['branch_id'=>$hub->id,'code'=>'SHELF-01'], ['type'=>'shelf','capacity'=>50,'status'=>'active']);
            WhLocation::firstOrCreate(['branch_id'=>$hub->id,'code'=>'CAGE-01'], ['type'=>'cage','capacity'=>20,'status'=>'active']);
        }
    }
}

