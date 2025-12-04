<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rate_cards')) {
            return;
        }

        $serviceLevels = [
            'economy' => ['multiplier' => 0.8, 'minimum_charge' => 12.00],
            'standard' => ['multiplier' => 1.0, 'minimum_charge' => 15.00],
            'express' => ['multiplier' => 1.5, 'minimum_charge' => 20.00],
            'priority' => ['multiplier' => 2.0, 'minimum_charge' => 25.00],
        ];

        foreach ($serviceLevels as $level => $config) {
            $exists = DB::table('rate_cards')
                ->where('service_level', $level)
                ->where('origin_country', 'XX')
                ->where('dest_country', 'XX')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('rate_cards')->insert([
                'name' => ucfirst($level) . ' Default',
                'origin_country' => 'XX',
                'dest_country' => 'XX',
                'origin_zones' => json_encode(['default']),
                'dest_zones' => json_encode(['default']),
                'service_level' => $level,
                'currency' => 'USD',
                'minimum_charge' => $config['minimum_charge'],
                'weight_breaks' => json_encode([
                    ['up_to_kg' => 5, 'rate_per_kg' => 5 * $config['multiplier']],
                    ['up_to_kg' => 20, 'rate_per_kg' => 4 * $config['multiplier']],
                    ['up_to_kg' => 50, 'rate_per_kg' => 3.5 * $config['multiplier']],
                    ['up_to_kg' => 9999, 'rate_per_kg' => 3 * $config['multiplier']],
                ]),
                'weight_rules' => json_encode([]),
                'dim_rules' => json_encode([]),
                'fuel_surcharge_percent' => 8,
                'security_surcharge' => 0,
                'remote_area_surcharge' => 0,
                'insurance_rate_percent' => 2,
                'zone_matrix' => json_encode(['A' => 5 * $config['multiplier']]),
                'accessorials' => json_encode([]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('rate_cards')) {
            return;
        }

        DB::table('rate_cards')
            ->where('origin_country', 'XX')
            ->where('dest_country', 'XX')
            ->whereIn('service_level', ['economy', 'standard', 'express', 'priority'])
            ->delete();
    }
};
