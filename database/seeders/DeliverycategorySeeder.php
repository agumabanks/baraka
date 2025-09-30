<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Backend\Deliverycategory;
use Illuminate\Database\Seeder;

class DeliverycategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $deliverycategorys = [
            'KG',
            'Mobile',
            'Laptop',
            'Tabs',
            'Gaming Kybord',
            'Cosmetices', ];
        $i = 0;
        for ($n = 0; $n < count($deliverycategorys); $n++) {
            $dep = new Deliverycategory;
            $dep->title = $deliverycategorys[$n];
            $dep->position = ++$i;
            $dep->status = Status::ACTIVE;
            $dep->save();
        }
    }
}
