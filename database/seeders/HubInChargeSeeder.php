<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Backend\HubInCharge;
use Illuminate\Database\Seeder;

class HubInChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $inCharge = new HubInCharge;
        $inCharge->user_id = 2;
        $inCharge->hub_id = 1;
        $inCharge->status = Status::ACTIVE;
        $inCharge->save();
    }
}
