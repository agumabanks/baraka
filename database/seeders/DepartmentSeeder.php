<?php

namespace Database\Seeders;

use App\Models\Backend\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            'General Management',
            'Marketing',
            'Operations',
            'Finance',
            'Sales',
            'Human Resource',
            'Purchase'];

        for ($n = 0; $n < count($departments); $n++) {
            $dep = new Department;
            $dep->title = $departments[$n];
            $dep->save();
        }
    }
}
