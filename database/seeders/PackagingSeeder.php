<?php

namespace Database\Seeders;

use App\Models\Backend\Packaging;
use Illuminate\Database\Seeder;

class PackagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $packages = ['Poly', 'Bubble Poly', 'Box', 'Box Poly'];
        $i = 1;
        foreach ($packages as $value) {

            $packaging = new Packaging;
            $packaging->name = $value;
            $packaging->price = $i.'0';
            $packaging->position = $i;
            $packaging->status = 1;
            $packaging->save();
            $i++;
        }
    }
}
