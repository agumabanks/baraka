<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnifiedBranchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Main HUB (Central)
        $mainHub = \App\Models\UnifiedBranch::create([
            'name' => 'Riyadh Central Hub',
            'code' => 'HUB-RYD-001',
            'type' => 'HUB',
            'is_hub' => true,
            'parent_branch_id' => null,
            'address' => 'King Fahd Road, Riyadh, Saudi Arabia',
            'phone' => '+966112345678',
            'email' => 'hub.riyadh@baraka.sanaa.co',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'operating_hours' => json_encode([
                'monday' => ['open' => '08:00', 'close' => '22:00'],
                'tuesday' => ['open' => '08:00', 'close' => '22:00'],
                'wednesday' => ['open' => '08:00', 'close' => '22:00'],
                'thursday' => ['open' => '08:00', 'close' => '22:00'],
                'friday' => ['open' => '14:00', 'close' => '22:00'],
                'saturday' => ['open' => '08:00', 'close' => '22:00'],
                'sunday' => ['open' => '08:00', 'close' => '22:00'],
            ]),
            'capabilities' => json_encode(['sorting', 'processing', 'customs', 'international', 'storage']),
            'metadata' => json_encode(['capacity' => 10000, 'sorting_lines' => 5, 'loading_docks' => 20]),
            'status' => 1,
        ]);

        // Regional Branches
        $jeddahRegional = \App\Models\UnifiedBranch::create([
            'name' => 'Jeddah Regional Center',
            'code' => 'REG-JED-001',
            'type' => 'REGIONAL',
            'is_hub' => false,
            'parent_branch_id' => $mainHub->id,
            'address' => 'Corniche Road, Jeddah, Saudi Arabia',
            'phone' => '+966126789012',
            'email' => 'regional.jeddah@baraka.sanaa.co',
            'latitude' => 21.4858,
            'longitude' => 39.1925,
            'operating_hours' => json_encode([
                'monday' => ['open' => '08:00', 'close' => '20:00'],
                'tuesday' => ['open' => '08:00', 'close' => '20:00'],
                'wednesday' => ['open' => '08:00', 'close' => '20:00'],
                'thursday' => ['open' => '08:00', 'close' => '20:00'],
                'friday' => ['open' => '14:00', 'close' => '20:00'],
                'saturday' => ['open' => '08:00', 'close' => '20:00'],
                'sunday' => ['open' => '08:00', 'close' => '20:00'],
            ]),
            'capabilities' => json_encode(['sorting', 'processing', 'storage', 'pickup', 'delivery']),
            'metadata' => json_encode(['capacity' => 5000, 'sorting_lines' => 3, 'loading_docks' => 10]),
            'status' => 1,
        ]);

        $dammamRegional = \App\Models\UnifiedBranch::create([
            'name' => 'Dammam Regional Center',
            'code' => 'REG-DMM-001',
            'type' => 'REGIONAL',
            'is_hub' => false,
            'parent_branch_id' => $mainHub->id,
            'address' => 'King Saud Road, Dammam, Saudi Arabia',
            'phone' => '+966138901234',
            'email' => 'regional.dammam@baraka.sanaa.co',
            'latitude' => 26.4207,
            'longitude' => 50.0888,
            'operating_hours' => json_encode([
                'monday' => ['open' => '08:00', 'close' => '20:00'],
                'tuesday' => ['open' => '08:00', 'close' => '20:00'],
                'wednesday' => ['open' => '08:00', 'close' => '20:00'],
                'thursday' => ['open' => '08:00', 'close' => '20:00'],
                'friday' => ['open' => '14:00', 'close' => '20:00'],
                'saturday' => ['open' => '08:00', 'close' => '20:00'],
                'sunday' => ['open' => '08:00', 'close' => '20:00'],
            ]),
            'capabilities' => json_encode(['sorting', 'processing', 'storage', 'pickup', 'delivery']),
            'metadata' => json_encode(['capacity' => 4000, 'sorting_lines' => 2, 'loading_docks' => 8]),
            'status' => 1,
        ]);

        // Local Branches under Jeddah Regional
        \App\Models\UnifiedBranch::create([
            'name' => 'Jeddah North Branch',
            'code' => 'LOC-JED-N01',
            'type' => 'LOCAL',
            'is_hub' => false,
            'parent_branch_id' => $jeddahRegional->id,
            'address' => 'Al Hamra District, Jeddah, Saudi Arabia',
            'phone' => '+966126789013',
            'email' => 'branch.jeddah.north@baraka.sanaa.co',
            'latitude' => 21.6258,
            'longitude' => 39.1569,
            'operating_hours' => json_encode([
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '14:00', 'close' => '18:00'],
                'saturday' => ['open' => '09:00', 'close' => '18:00'],
                'sunday' => ['open' => 'closed', 'close' => 'closed'],
            ]),
            'capabilities' => json_encode(['pickup', 'delivery', 'dropoff']),
            'metadata' => json_encode(['capacity' => 500, 'vehicles' => 5]),
            'status' => 1,
        ]);

        \App\Models\UnifiedBranch::create([
            'name' => 'Jeddah South Branch',
            'code' => 'LOC-JED-S01',
            'type' => 'LOCAL',
            'is_hub' => false,
            'parent_branch_id' => $jeddahRegional->id,
            'address' => 'Al Khalidiyah District, Jeddah, Saudi Arabia',
            'phone' => '+966126789014',
            'email' => 'branch.jeddah.south@baraka.sanaa.co',
            'latitude' => 21.4224,
            'longitude' => 39.2192,
            'operating_hours' => json_encode([
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '14:00', 'close' => '18:00'],
                'saturday' => ['open' => '09:00', 'close' => '18:00'],
                'sunday' => ['open' => 'closed', 'close' => 'closed'],
            ]),
            'capabilities' => json_encode(['pickup', 'delivery', 'dropoff']),
            'metadata' => json_encode(['capacity' => 500, 'vehicles' => 5]),
            'status' => 1,
        ]);

        // Local Branches under Dammam Regional
        \App\Models\UnifiedBranch::create([
            'name' => 'Dammam City Branch',
            'code' => 'LOC-DMM-C01',
            'type' => 'LOCAL',
            'is_hub' => false,
            'parent_branch_id' => $dammamRegional->id,
            'address' => 'Al Faisaliyah District, Dammam, Saudi Arabia',
            'phone' => '+966138901235',
            'email' => 'branch.dammam.city@baraka.sanaa.co',
            'latitude' => 26.4393,
            'longitude' => 50.1034,
            'operating_hours' => json_encode([
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '14:00', 'close' => '18:00'],
                'saturday' => ['open' => '09:00', 'close' => '18:00'],
                'sunday' => ['open' => 'closed', 'close' => 'closed'],
            ]),
            'capabilities' => json_encode(['pickup', 'delivery', 'dropoff']),
            'metadata' => json_encode(['capacity' => 400, 'vehicles' => 4]),
            'status' => 1,
        ]);

        // Local Branches under Main Hub (Riyadh)
        \App\Models\UnifiedBranch::create([
            'name' => 'Riyadh North Branch',
            'code' => 'LOC-RYD-N01',
            'type' => 'LOCAL',
            'is_hub' => false,
            'parent_branch_id' => $mainHub->id,
            'address' => 'Al Olaya District, Riyadh, Saudi Arabia',
            'phone' => '+966112345679',
            'email' => 'branch.riyadh.north@baraka.sanaa.co',
            'latitude' => 24.7743,
            'longitude' => 46.6695,
            'operating_hours' => json_encode([
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '14:00', 'close' => '18:00'],
                'saturday' => ['open' => '09:00', 'close' => '18:00'],
                'sunday' => ['open' => 'closed', 'close' => 'closed'],
            ]),
            'capabilities' => json_encode(['pickup', 'delivery', 'dropoff']),
            'metadata' => json_encode(['capacity' => 600, 'vehicles' => 6]),
            'status' => 1,
        ]);

        \App\Models\UnifiedBranch::create([
            'name' => 'Riyadh South Branch',
            'code' => 'LOC-RYD-S01',
            'type' => 'LOCAL',
            'is_hub' => false,
            'parent_branch_id' => $mainHub->id,
            'address' => 'Al Malaz District, Riyadh, Saudi Arabia',
            'phone' => '+966112345680',
            'email' => 'branch.riyadh.south@baraka.sanaa.co',
            'latitude' => 24.6478,
            'longitude' => 46.7209,
            'operating_hours' => json_encode([
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '14:00', 'close' => '18:00'],
                'saturday' => ['open' => '09:00', 'close' => '18:00'],
                'sunday' => ['open' => 'closed', 'close' => 'closed'],
            ]),
            'capabilities' => json_encode(['pickup', 'delivery', 'dropoff']),
            'metadata' => json_encode(['capacity' => 600, 'vehicles' => 6]),
            'status' => 1,
        ]);

        $this->command->info('Created 8 branches: 1 HUB, 2 REGIONAL, 5 LOCAL');
    }
}
