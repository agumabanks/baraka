<?php

namespace Database\Seeders;

use App\Models\Backend\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class BranchSeeder extends Seeder
{
    /**
     * Idempotent branch seeder - safe for production use
     * Uses updateOrCreate to avoid duplicates
     * Configurable via environment variables
     */
    public function run(): void
    {
        $branchConfig = config('seeders.branches', []);
        
        if (empty($branchConfig)) {
            $this->seedDefaultBranches();
        } else {
            $this->seedConfiguredBranches($branchConfig);
        }

        Log::info('Branch seeding completed', [
            'total_branches' => Branch::count(),
        ]);
    }

    private function seedDefaultBranches(): void
    {
        $branches = [
            // HUB Level
            [
                'code' => 'HUB-DUBAI',
                'name' => 'Dubai Main Hub',
                'type' => 'HUB',
                'country' => 'AE',
                'city' => 'Dubai',
                'address' => 'Dubai International City',
                'is_hub' => true,
                'parent_id' => null,
                'status' => 'active',
            ],
            [
                'code' => 'HUB-ABU-DHABI',
                'name' => 'Abu Dhabi Hub',
                'type' => 'HUB',
                'country' => 'AE',
                'city' => 'Abu Dhabi',
                'address' => 'Abu Dhabi Industrial Zone',
                'is_hub' => true,
                'parent_id' => null,
                'status' => 'active',
            ],
            // REGIONAL Level
            [
                'code' => 'REG-DUBAI-NORTH',
                'name' => 'Dubai North Regional',
                'type' => 'REGIONAL',
                'country' => 'AE',
                'city' => 'Dubai',
                'address' => 'Dubai Silicon Oasis',
                'is_hub' => false,
                'parent_id' => null, // Will link to HUB-DUBAI
                'status' => 'active',
            ],
            [
                'code' => 'REG-DUBAI-SOUTH',
                'name' => 'Dubai South Regional',
                'type' => 'REGIONAL',
                'country' => 'AE',
                'city' => 'Dubai',
                'address' => 'Dubai South Logistics City',
                'is_hub' => false,
                'parent_id' => null, // Will link to HUB-DUBAI
                'status' => 'active',
            ],
            // LOCAL Level
            [
                'code' => 'LOC-DUBAI-DIPS',
                'name' => 'Dubai DIPS Local',
                'type' => 'LOCAL',
                'country' => 'AE',
                'city' => 'Dubai',
                'address' => 'Dubai Investment Park',
                'is_hub' => false,
                'parent_id' => null, // Will link to REG-DUBAI-NORTH
                'status' => 'active',
            ],
        ];

        $this->createOrUpdateBranches($branches);
    }

    private function seedConfiguredBranches(array $branchConfig): void
    {
        foreach ($branchConfig as $config) {
            if (!isset($config['code'], $config['name'])) {
                Log::warning('Invalid branch config - missing code or name', ['config' => $config]);
                continue;
            }

            try {
                $this->createOrUpdateBranches([$config]);
            } catch (\Throwable $e) {
                Log::error('Failed to seed branch', [
                    'code' => $config['code'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function createOrUpdateBranches(array $branches): void
    {
        $hubMap = [];

        // First pass: create all branches
        foreach ($branches as $branchData) {
            $branch = Branch::updateOrCreate(
                ['code' => $branchData['code']],
                $branchData
            );

            $hubMap[$branchData['code']] = $branch;
            
            Log::info('Branch seeded', [
                'code' => $branch->code,
                'name' => $branch->name,
                'created' => $branch->wasRecentlyCreated,
            ]);
        }

        // Second pass: link parent relationships if configured
        foreach ($branches as $branchData) {
            if (isset($branchData['parent_code'])) {
                $branch = $hubMap[$branchData['code']];
                $parent = $hubMap[$branchData['parent_code']] ?? null;
                
                if ($parent) {
                    $branch->update(['parent_id' => $parent->id]);
                    Log::info('Branch parent linked', [
                        'child' => $branch->code,
                        'parent' => $parent->code,
                    ]);
                }
            }
        }
    }
}
