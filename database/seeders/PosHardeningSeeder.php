<?php

namespace Database\Seeders;

use App\Models\Backend\Branch;
use App\Models\RouteCapability;
use App\Models\RouteTemplate;
use App\Models\ServiceConstraint;
use App\Models\Tariff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PosHardeningSeeder extends Seeder
{
    /**
     * Tariff data based on Baraka Business price card (from Istanbul)
     * Prices are per kg in USD
     */
    protected array $tariffData = [
        // International destinations
        'Luanda' => ['price' => 14.50, 'zone' => 'international', 'country' => 'Angola'],
        'Brazzaville' => ['price' => 13.50, 'zone' => 'international', 'country' => 'Congo'],
        'Pointe Noire' => ['price' => 14.00, 'zone' => 'international', 'country' => 'Congo'],
        
        // National (DRC) destinations
        'Kolwezi' => ['price' => 15.00, 'zone' => 'national_drc', 'country' => 'DRC'],
        'Kasumbalesa' => ['price' => 15.00, 'zone' => 'national_drc', 'country' => 'DRC'],
        'Likasi' => ['price' => 15.00, 'zone' => 'national_drc', 'country' => 'DRC'],
        'Kinshasa' => ['price' => 12.00, 'zone' => 'national_drc', 'country' => 'DRC'],
        'Matadi' => ['price' => 12.50, 'zone' => 'national_drc', 'country' => 'DRC'],
        'Goma' => ['price' => 14.00, 'zone' => 'national_drc', 'country' => 'DRC'],
        'Lubumbashi' => ['price' => 14.50, 'zone' => 'national_drc', 'country' => 'DRC'],
    ];

    /**
     * Service level multipliers
     */
    protected array $serviceLevels = [
        'economy' => ['multiplier' => 0.80, 'days' => '7-10', 'max_weight' => 1000],
        'standard' => ['multiplier' => 1.00, 'days' => '5-7', 'max_weight' => 500],
        'express' => ['multiplier' => 1.50, 'days' => '3-5', 'max_weight' => 100],
        'priority' => ['multiplier' => 2.00, 'days' => '1-3', 'max_weight' => 50],
    ];

    public function run(): void
    {
        $this->command->info('Seeding POS Hardening data...');

        DB::transaction(function () {
            $this->seedBranches();
            $this->seedTariffs();
            $this->seedRouteCapabilities();
            $this->seedServiceConstraints();
            $this->seedRouteTemplates();
        });

        $this->command->info('POS Hardening data seeded successfully!');
    }

    protected function seedBranches(): void
    {
        $this->command->info('  - Creating missing branches...');

        // Istanbul as origin hub
        $istanbul = Branch::firstOrCreate(
            ['code' => 'IST'],
            [
                'name' => 'Istanbul Hub',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'Istanbul, Turkey',
                'phone' => '+90 545 912 33 81',
                'email' => 'barakabusiness@gmail.com',
                'status' => 'active',
                'type' => 'hub',
                'is_hub' => true,
            ]
        );
        $this->command->info("    Istanbul Hub: ID {$istanbul->id}");

        // Missing DRC branches
        $missingBranches = [
            ['code' => 'KLZ', 'name' => 'Kolwezi', 'city' => 'Kolwezi', 'country' => 'DRC'],
            ['code' => 'KSB', 'name' => 'Kasumbalesa', 'city' => 'Kasumbalesa', 'country' => 'DRC'],
            ['code' => 'LKS', 'name' => 'Likasi', 'city' => 'Likasi', 'country' => 'DRC'],
        ];

        foreach ($missingBranches as $branch) {
            $created = Branch::firstOrCreate(
                ['code' => $branch['code']],
                array_merge($branch, [
                    'status' => 'active',
                    'type' => 'branch',
                    'is_hub' => false,
                ])
            );
            $this->command->info("    {$branch['name']}: ID {$created->id}");
        }

        // Update existing branches with correct country info
        $updates = [
            'LDA' => ['country' => 'Angola'],
            'BZL' => ['country' => 'Congo'],
            'PTN' => ['country' => 'Congo'],
            'GMA' => ['country' => 'DRC', 'city' => 'Goma'],
            'MTD' => ['country' => 'DRC', 'city' => 'Matadi'],
            'KSS' => ['country' => 'DRC', 'city' => 'Kinshasa'],
            'LBB' => ['country' => 'DRC', 'city' => 'Lubumbashi'],
        ];

        foreach ($updates as $code => $data) {
            Branch::where('code', $code)->update($data);
        }
    }

    protected function seedTariffs(): void
    {
        $this->command->info('  - Seeding tariffs...');

        $istanbul = Branch::where('code', 'IST')->first();
        if (!$istanbul) {
            $this->command->error('    Istanbul branch not found!');
            return;
        }

        $tariffCount = 0;
        $effectiveFrom = now()->startOfMonth();

        foreach ($this->tariffData as $destName => $data) {
            $destBranch = Branch::where('name', $destName)->first();
            if (!$destBranch) {
                $this->command->warn("    Branch '{$destName}' not found, skipping...");
                continue;
            }

            foreach ($this->serviceLevels as $service => $serviceData) {
                // Calculate rate based on service multiplier
                $baseRate = $data['price'] * $serviceData['multiplier'];
                
                // Create weight-based tariff bands
                $weightBands = [
                    ['from' => 0, 'to' => 5, 'rate_adjust' => 1.20],      // Small parcels premium
                    ['from' => 5, 'to' => 20, 'rate_adjust' => 1.00],     // Standard rate
                    ['from' => 20, 'to' => 100, 'rate_adjust' => 0.95],   // Volume discount
                    ['from' => 100, 'to' => null, 'rate_adjust' => 0.90], // Bulk discount
                ];

                foreach ($weightBands as $band) {
                    Tariff::updateOrCreate(
                        [
                            'service_level' => $service,
                            'zone' => $data['zone'],
                            'weight_from' => $band['from'],
                            'weight_to' => $band['to'],
                        ],
                        [
                            'name' => "Istanbul to {$destName} - " . ucfirst($service),
                            'base_rate' => round($baseRate * 2, 2), // Base handling fee
                            'per_kg_rate' => round($baseRate * $band['rate_adjust'], 2),
                            'fuel_surcharge_percent' => 8.00,
                            'currency' => 'USD',
                            'version' => '2024.12',
                            'effective_from' => $effectiveFrom,
                            'effective_to' => null,
                            'active' => true,
                        ]
                    );
                    $tariffCount++;
                }
            }
        }

        $this->command->info("    Created/updated {$tariffCount} tariff entries");
    }

    protected function seedRouteCapabilities(): void
    {
        $this->command->info('  - Seeding route capabilities...');

        $istanbul = Branch::where('code', 'IST')->first();
        if (!$istanbul) {
            return;
        }

        $allBranches = Branch::where('code', '!=', 'IST')->get();
        $capCount = 0;

        foreach ($allBranches as $destBranch) {
            foreach ($this->serviceLevels as $service => $serviceData) {
                // Determine if hazmat/COD is allowed based on zone
                $isInternational = !in_array($destBranch->country, ['DRC']);
                
                RouteCapability::updateOrCreate(
                    [
                        'origin_branch_id' => $istanbul->id,
                        'destination_branch_id' => $destBranch->id,
                        'service_level' => $service,
                    ],
                    [
                        'max_weight' => $serviceData['max_weight'],
                        'hazmat_allowed' => !$isInternational && $service !== 'priority', // No hazmat for priority or international
                        'cod_allowed' => !$isInternational, // COD only for national
                        'status' => 'active',
                    ]
                );
                $capCount++;
            }

            // Also create reverse route (from destination back to Istanbul)
            foreach (['standard', 'express'] as $service) {
                RouteCapability::updateOrCreate(
                    [
                        'origin_branch_id' => $destBranch->id,
                        'destination_branch_id' => $istanbul->id,
                        'service_level' => $service,
                    ],
                    [
                        'max_weight' => $this->serviceLevels[$service]['max_weight'],
                        'hazmat_allowed' => false,
                        'cod_allowed' => false,
                        'status' => 'active',
                    ]
                );
                $capCount++;
            }

            // Inter-DRC routes (between DRC branches)
            if ($destBranch->country === 'DRC') {
                $drcBranches = Branch::where('country', 'DRC')
                    ->where('id', '!=', $destBranch->id)
                    ->get();

                foreach ($drcBranches as $otherDrc) {
                    foreach (['standard', 'express'] as $service) {
                        RouteCapability::updateOrCreate(
                            [
                                'origin_branch_id' => $destBranch->id,
                                'destination_branch_id' => $otherDrc->id,
                                'service_level' => $service,
                            ],
                            [
                                'max_weight' => $this->serviceLevels[$service]['max_weight'],
                                'hazmat_allowed' => true,
                                'cod_allowed' => true,
                                'status' => 'active',
                            ]
                        );
                        $capCount++;
                    }
                }
            }
        }

        $this->command->info("    Created/updated {$capCount} route capability entries");
    }

    protected function seedServiceConstraints(): void
    {
        $this->command->info('  - Seeding service constraints...');

        $constraints = [
            ['service' => 'economy', 'min' => 0.5, 'max' => 1000, 'min_value' => null, 'max_value' => 10000],
            ['service' => 'standard', 'min' => 0.1, 'max' => 500, 'min_value' => null, 'max_value' => 50000],
            ['service' => 'express', 'min' => 0.1, 'max' => 100, 'min_value' => null, 'max_value' => 100000],
            ['service' => 'priority', 'min' => 0.1, 'max' => 50, 'min_value' => 100, 'max_value' => null], // Priority requires min declared value
        ];

        foreach ($constraints as $c) {
            ServiceConstraint::updateOrCreate(
                [
                    'service_level' => $c['service'],
                    'origin_branch_id' => null,
                    'destination_branch_id' => null,
                ],
                [
                    'min_weight' => $c['min'],
                    'max_weight' => $c['max'],
                    'min_declared_value' => $c['min_value'],
                    'max_declared_value' => $c['max_value'],
                    'active' => true,
                ]
            );
        }

        $this->command->info('    Created 4 global service constraints');
    }

    protected function seedRouteTemplates(): void
    {
        $this->command->info('  - Seeding route templates...');

        $istanbul = Branch::where('code', 'IST')->first();
        if (!$istanbul) {
            return;
        }

        $popularRoutes = [
            ['dest' => 'Kinshasa', 'service' => 'standard', 'name' => 'Istanbul > Kinshasa (Standard)'],
            ['dest' => 'Kinshasa', 'service' => 'express', 'name' => 'Istanbul > Kinshasa (Express)'],
            ['dest' => 'Lubumbashi', 'service' => 'standard', 'name' => 'Istanbul > Lubumbashi (Standard)'],
            ['dest' => 'Goma', 'service' => 'standard', 'name' => 'Istanbul > Goma (Standard)'],
            ['dest' => 'Luanda', 'service' => 'standard', 'name' => 'Istanbul > Luanda (Standard)'],
            ['dest' => 'Brazzaville', 'service' => 'standard', 'name' => 'Istanbul > Brazzaville (Standard)'],
        ];

        $templateCount = 0;
        foreach ($popularRoutes as $route) {
            $destBranch = Branch::where('name', $route['dest'])->first();
            if (!$destBranch) {
                continue;
            }

            RouteTemplate::updateOrCreate(
                [
                    'origin_branch_id' => $istanbul->id,
                    'destination_branch_id' => $destBranch->id,
                    'default_service_level' => $route['service'],
                ],
                [
                    'name' => $route['name'],
                    'active' => true,
                ]
            );
            $templateCount++;
        }

        $this->command->info("    Created {$templateCount} route templates");
    }
}
