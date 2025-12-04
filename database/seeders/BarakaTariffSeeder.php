<?php

namespace Database\Seeders;

use App\Models\Backend\Branch;
use App\Models\RouteCapability;
use App\Models\ServiceConstraint;
use App\Models\Tariff;
use App\Support\SystemSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Baraka Business Tariff Seeder
 * 
 * Based on official price card: "TARIFICATION DES COLIS A PARTIR D'ISTANBUL"
 * All rates are per kg in USD
 * Service level multipliers and constraints are read from SystemSettings
 */
class BarakaTariffSeeder extends Seeder
{
    /**
     * Official Baraka Business rates from Istanbul
     * Source: Price card image dated 2024
     */
    protected array $officialRates = [
        // International destinations
        'Luanda' => ['rate' => 14.50, 'country' => 'Angola', 'code' => 'LDA', 'type' => 'international'],
        'Brazzaville' => ['rate' => 13.50, 'country' => 'Congo', 'code' => 'BZL', 'type' => 'international'],
        'Pointe Noire' => ['rate' => 14.00, 'country' => 'Congo', 'code' => 'PTN', 'type' => 'international'],
        
        // National DRC destinations
        'Kolwezi' => ['rate' => 15.00, 'country' => 'DRC', 'code' => 'KLZ', 'type' => 'national'],
        'Kasumbalesa' => ['rate' => 15.00, 'country' => 'DRC', 'code' => 'KSB', 'type' => 'national'],
        'Likasi' => ['rate' => 15.00, 'country' => 'DRC', 'code' => 'LKS', 'type' => 'national'],
        'Kinshasa' => ['rate' => 12.00, 'country' => 'DRC', 'code' => 'KSS', 'type' => 'national'],
        'Matadi' => ['rate' => 12.50, 'country' => 'DRC', 'code' => 'MTD', 'type' => 'national'],
        'Goma' => ['rate' => 14.00, 'country' => 'DRC', 'code' => 'GMA', 'type' => 'national'],
        'Lubumbashi' => ['rate' => 14.50, 'country' => 'DRC', 'code' => 'LBB', 'type' => 'national'],
    ];

    /**
     * Get service level configuration from SystemSettings
     * Admins can adjust multipliers and constraints in Settings > Pricing
     */
    protected function getServiceLevels(): array
    {
        $multipliers = SystemSettings::serviceLevelMultipliers();
        $constraints = SystemSettings::serviceLevelConstraints();
        
        return [
            'economy' => [
                'multiplier' => $multipliers['economy'] ?? 0.80,
                'transit_days' => $constraints['economy']['transit_days'] ?? '7-10 days',
                'max_weight' => $constraints['economy']['max_weight'] ?? 1000,
                'min_weight' => 1,
            ],
            'standard' => [
                'multiplier' => $multipliers['standard'] ?? 1.00,
                'transit_days' => $constraints['standard']['transit_days'] ?? '5-7 days',
                'max_weight' => $constraints['standard']['max_weight'] ?? 500,
                'min_weight' => 0.5,
            ],
            'express' => [
                'multiplier' => $multipliers['express'] ?? 1.50,
                'transit_days' => $constraints['express']['transit_days'] ?? '3-5 days',
                'max_weight' => $constraints['express']['max_weight'] ?? 100,
                'min_weight' => 0.1,
            ],
            'priority' => [
                'multiplier' => $multipliers['priority'] ?? 2.00,
                'transit_days' => $constraints['priority']['transit_days'] ?? '1-3 days',
                'max_weight' => $constraints['priority']['max_weight'] ?? 50,
                'min_weight' => 0.1,
            ],
        ];
    }

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║     BARAKA BUSINESS - TARIFF CONFIGURATION                   ║');
        $this->command->info('║     Source: Istanbul Hub Price Card                          ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
        $this->command->info('');

        DB::transaction(function () {
            $this->setupBranches();
            $this->clearOldTariffs();
            $this->createTariffs();
            $this->createRouteCapabilities();
            $this->createServiceConstraints();
        });

        $this->command->info('');
        $this->command->info('✓ Tariff configuration complete!');
        $this->command->info('');
    }

    protected function setupBranches(): void
    {
        $this->command->info('1. Setting up branches...');

        // Ensure Istanbul Hub exists and is active
        $istanbul = Branch::updateOrCreate(
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
        $this->command->info("   ✓ Istanbul Hub (ID: {$istanbul->id}) - ORIGIN");

        // Ensure all destination branches exist and are active
        foreach ($this->officialRates as $name => $data) {
            $branch = Branch::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $name,
                    'city' => $name,
                    'country' => $data['country'],
                    'status' => 'active',
                    'type' => 'branch',
                    'is_hub' => false,
                ]
            );
            $this->command->info("   ✓ {$name} ({$data['code']}, ID: {$branch->id}) - \${$data['rate']}/kg");
        }
    }

    protected function clearOldTariffs(): void
    {
        $this->command->info('');
        $this->command->info('2. Clearing old tariff data...');
        
        $deleted = Tariff::where('version', 'like', 'BARAKA-%')->delete();
        $this->command->info("   ✓ Removed {$deleted} old BARAKA tariff entries");
    }

    protected function createTariffs(): void
    {
        $this->command->info('');
        $this->command->info('3. Creating tariffs based on official price card...');
        $this->command->info('');
        $this->command->info('   ┌─────────────────────┬──────────┬──────────┬──────────┬──────────┐');
        $this->command->info('   │ Destination         │ Economy  │ Standard │ Express  │ Priority │');
        $this->command->info('   ├─────────────────────┼──────────┼──────────┼──────────┼──────────┤');

        $istanbul = Branch::where('code', 'IST')->first();
        $tariffCount = 0;
        $effectiveFrom = now()->startOfMonth();
        $version = 'BARAKA-2024.12';

        foreach ($this->officialRates as $destName => $data) {
            $destBranch = Branch::where('code', $data['code'])->first();
            if (!$destBranch) {
                $this->command->warn("   Branch {$destName} not found!");
                continue;
            }

            $baseRate = $data['rate'];
            $prices = [];

            foreach ($this->getServiceLevels() as $service => $serviceConfig) {
                $perKgRate = round($baseRate * $serviceConfig['multiplier'], 2);
                $prices[$service] = $perKgRate;

                // Create tariff entry for this specific route + service
                Tariff::create([
                    'name' => "Istanbul → {$destName}",
                    'service_level' => $service,
                    'zone' => "IST-{$data['code']}", // Route-specific zone
                    'weight_from' => 0,
                    'weight_to' => null, // No upper limit
                    'base_rate' => round($perKgRate * 2, 2), // Fixed handling fee
                    'per_kg_rate' => $perKgRate,
                    'fuel_surcharge_percent' => 8.00,
                    'currency' => 'USD',
                    'version' => $version,
                    'effective_from' => $effectiveFrom,
                    'effective_to' => null,
                    'active' => true,
                ]);
                $tariffCount++;
            }

            $this->command->info(sprintf(
                '   │ %-19s │ $%-7.2f │ $%-7.2f │ $%-7.2f │ $%-7.2f │',
                $destName,
                $prices['economy'],
                $prices['standard'],
                $prices['express'],
                $prices['priority']
            ));
        }

        $this->command->info('   └─────────────────────┴──────────┴──────────┴──────────┴──────────┘');
        $this->command->info("   ✓ Created {$tariffCount} tariff entries");
    }

    protected function createRouteCapabilities(): void
    {
        $this->command->info('');
        $this->command->info('4. Creating route capabilities...');

        $istanbul = Branch::where('code', 'IST')->first();
        $capCount = 0;

        // Clear old capabilities from Istanbul
        RouteCapability::where('origin_branch_id', $istanbul->id)->delete();

        foreach ($this->officialRates as $destName => $data) {
            $destBranch = Branch::where('code', $data['code'])->first();
            if (!$destBranch) continue;

            $isInternational = $data['type'] === 'international';

            foreach ($this->getServiceLevels() as $service => $serviceConfig) {
                RouteCapability::create([
                    'origin_branch_id' => $istanbul->id,
                    'destination_branch_id' => $destBranch->id,
                    'service_level' => $service,
                    'max_weight' => $serviceConfig['max_weight'],
                    'hazmat_allowed' => !$isInternational && $service !== 'priority',
                    'cod_allowed' => !$isInternational, // COD only for DRC national
                    'status' => 'active',
                ]);
                $capCount++;
            }
        }

        $this->command->info("   ✓ Created {$capCount} route capability entries");
        $this->command->info("   ✓ COD enabled for: DRC national routes only");
        $this->command->info("   ✓ Hazmat allowed for: DRC routes (except priority)");
    }

    protected function createServiceConstraints(): void
    {
        $this->command->info('');
        $this->command->info('5. Creating service constraints...');

        // Clear old constraints
        ServiceConstraint::truncate();

        foreach ($this->getServiceLevels() as $service => $config) {
            ServiceConstraint::create([
                'service_level' => $service,
                'origin_branch_id' => null, // Global
                'destination_branch_id' => null, // Global
                'min_weight' => $config['min_weight'],
                'max_weight' => $config['max_weight'],
                'min_declared_value' => $service === 'priority' ? 100 : null,
                'max_declared_value' => null,
                'active' => true,
            ]);

            $this->command->info("   ✓ {$service}: {$config['min_weight']}kg - {$config['max_weight']}kg ({$config['transit_days']})");
        }
    }
}
