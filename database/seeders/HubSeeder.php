<?php

namespace Database\Seeders;

use App\Models\Backend\Hub;
use Illuminate\Database\Seeder;

class HubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hubs = [
            [
                'name' => 'Mirpur-10',
                'phone' => '01000000001',
                'address' => 'Dhaka, Bangladesh',
                'current_balance' => '00',
            ],
            [
                'name' => 'Uttara',
                'phone' => '01000000002',
                'address' => 'Dhaka, Bangladesh',
                'current_balance' => '00',
            ],
            [
                'name' => 'Dhanmundi',
                'phone' => '01000000003',
                'address' => 'Dhaka, Bangladesh',
                'current_balance' => '00',
            ],
            [
                'name' => 'Old Dhaka',
                'phone' => '01000000004',
                'address' => 'Dhaka, Bangladesh',
                'current_balance' => '00',
            ],
            [
                'name' => 'Jatrabari',
                'phone' => '01000000005',
                'address' => 'Dhaka, Bangladesh',
                'current_balance' => '00',
            ],
            [
                'name' => 'Badda',
                'phone' => '01000000006',
                'address' => 'Dhaka, Bangladesh',
                'current_balance' => '00',
            ],
        ];

        for ($n = 0; $n < count($hubs); $n++) {
            $code = sprintf('BR%03d', $n + 1);
            Hub::updateOrCreate(
                ['branch_code' => $code],
                [
                    'name' => $hubs[$n]['name'],
                    'phone' => $hubs[$n]['phone'],
                    'address' => $hubs[$n]['address'],
                    'current_balance' => $hubs[$n]['current_balance'],
                ]
            );
        }
    }
}
