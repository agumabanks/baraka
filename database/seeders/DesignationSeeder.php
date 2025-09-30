<?php

namespace Database\Seeders;

use App\Models\Backend\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $designations = [
            'Chief Executive Officer (CEO)',
            'Chief Operating Officer (COO)',
            'Chief Financial Officer (CFO)',
            'Chief Technology Officer (CTO)',
            'Chief Legal Officer (CLO)',
            'Chief Marketing Officer (CMO)'];

        for ($n = 0; $n < count($designations); $n++) {
            $desig = new Designation;
            $desig->title = $designations[$n];
            $desig->save();
        }
    }
}
