<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Database optimization for Dynamic Pricing System
 * 
 * This migration adds proper indexes to optimize quote calculations,
 * customer lookups, and competitor pricing queries.
 * 
 * Safe migration that handles existing indexes and missing tables gracefully.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->safeAddIndexes('quotations', [
            ['columns' => ['customer_id', 'status'], 'name' => 'idx_quotation_customer_status_v2'],
            ['columns' => ['origin_branch_id', 'destination_country'], 'name' => 'idx_quotation_route_v2'],
            ['columns' => ['service_type', 'created_at'], 'name' => 'idx_quotation_service_date_v2'],
            ['columns' => ['valid_until', 'status'], 'name' => 'idx_quotation_validity_v2'],
            ['columns' => ['customer_id', 'created_at'], 'name' => 'idx_quotation_customer_date_v2'],
            ['columns' => ['customer_id', 'service_type', 'created_at'], 'name' => 'idx_quotation_customer_service_date_v2'],
            ['columns' => ['customer_id', 'created_at', 'total_amount', 'service_type'], 'name' => 'idx_quotation_customer_history_v2'],
        ]);

        $this->safeAddIndexes('rate_cards', [
            ['columns' => ['origin_country', 'dest_country', 'is_active'], 'name' => 'idx_ratecard_route_active_v2'],
            ['columns' => ['is_active', 'created_at'], 'name' => 'idx_ratecard_active_date_v2'],
        ]);

        $this->safeAddIndexes('pricing_rules', [
            ['columns' => ['rule_type', 'active', 'priority'], 'name' => 'idx_pricingrule_type_active_priority_v2'],
            ['columns' => ['active', 'effective_from', 'effective_to'], 'name' => 'idx_pricingrule_validity_v2'],
        ]);

        $this->safeAddIndexes('competitor_prices', [
            ['columns' => ['origin_country', 'destination_country', 'service_level'], 'name' => 'idx_competitor_route_service_v2'],
            ['columns' => ['carrier_name', 'collected_at'], 'name' => 'idx_competitor_carrier_date_v2'],
            ['columns' => ['source_type', 'collected_at'], 'name' => 'idx_competitor_source_date_v2'],
            ['columns' => ['origin_country', 'destination_country', 'service_level', 'collected_at'], 'name' => 'idx_competitor_benchmarking_v2'],
        ]);

        $this->safeAddIndexes('fuel_indices', [
            ['columns' => ['source', 'region', 'effective_date'], 'name' => 'idx_fuel_source_region_date_v2'],
            ['columns' => ['effective_date'], 'name' => 'idx_fuel_effective_date_v2'],
            ['columns' => ['source', 'effective_date'], 'name' => 'idx_fuel_surcharge_calc_v2'],
        ]);

        $this->safeAddIndexes('service_level_definitions', [
            ['columns' => ['code'], 'name' => 'idx_servicelevel_code_v2'],
        ]);

        $this->safeAddIndexes('surcharge_rules', [
            ['columns' => ['code', 'active'], 'name' => 'idx_surcharge_code_active_v2'],
            ['columns' => ['active_from', 'active_to'], 'name' => 'idx_surcharge_validity_v2'],
        ]);

        $this->safeAddIndexes('zones', [
            ['columns' => ['code'], 'name' => 'idx_zone_code_v2'],
        ]);

        $this->safeAddIndexes('customers', [
            ['columns' => ['customer_type', 'status'], 'name' => 'idx_customer_type_status_v2'],
            ['columns' => ['total_shipments', 'total_spent'], 'name' => 'idx_customer_volume_value_v2'],
            ['columns' => ['priority_level', 'customer_since'], 'name' => 'idx_customer_priority_since_v2'],
        ]);

        $this->safeAddIndexes('shipments', [
            ['columns' => ['customer_id', 'service_level', 'current_status'], 'name' => 'idx_shipment_customer_service_status_v2'],
            ['columns' => ['origin_branch_id', 'dest_branch_id', 'current_status'], 'name' => 'idx_shipment_route_status_v2'],
            ['columns' => ['service_level', 'created_at'], 'name' => 'idx_shipment_service_date_v2'],
            ['columns' => ['service_level', 'created_at'], 'name' => 'idx_shipment_dimensional_calc_v2'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes safely - ignore if they don't exist
        $this->safeDropIndexes('quotations', [
            'idx_quotation_customer_status_v2',
            'idx_quotation_route_v2',
            'idx_quotation_service_date_v2',
            'idx_quotation_validity_v2',
            'idx_quotation_customer_date_v2',
            'idx_quotation_customer_service_date_v2',
            'idx_quotation_customer_history_v2',
        ]);

        $this->safeDropIndexes('rate_cards', [
            'idx_ratecard_route_active_v2',
            'idx_ratecard_active_date_v2',
        ]);

        $this->safeDropIndexes('pricing_rules', [
            'idx_pricingrule_type_active_priority_v2',
            'idx_pricingrule_validity_v2',
        ]);

        $this->safeDropIndexes('competitor_prices', [
            'idx_competitor_route_service_v2',
            'idx_competitor_carrier_date_v2',
            'idx_competitor_source_date_v2',
            'idx_competitor_benchmarking_v2',
        ]);

        $this->safeDropIndexes('fuel_indices', [
            'idx_fuel_source_region_date_v2',
            'idx_fuel_effective_date_v2',
            'idx_fuel_surcharge_calc_v2',
        ]);

        $this->safeDropIndexes('service_level_definitions', [
            'idx_servicelevel_code_v2',
        ]);

        $this->safeDropIndexes('surcharge_rules', [
            'idx_surcharge_code_active_v2',
            'idx_surcharge_validity_v2',
        ]);

        $this->safeDropIndexes('zones', [
            'idx_zone_code_v2',
        ]);

        $this->safeDropIndexes('customers', [
            'idx_customer_type_status_v2',
            'idx_customer_volume_value_v2',
            'idx_customer_priority_since_v2',
        ]);

        $this->safeDropIndexes('shipments', [
            'idx_shipment_customer_service_status_v2',
            'idx_shipment_route_status_v2',
            'idx_shipment_service_date_v2',
            'idx_shipment_dimensional_calc_v2',
        ]);
    }

    /**
     * Safely add indexes to a table if it exists and indexes don't already exist
     */
    private function safeAddIndexes(string $table, array $indexes): void
    {
        // Check if table exists
        if (!Schema::hasTable($table)) {
            echo "Skipping index creation for non-existent table: {$table}\n";
            return;
        }

        $existingIndexes = [];
        try {
            $driver = Schema::getConnection()->getDriverName();
            
            if ($driver === 'sqlite') {
                $rawIndexes = DB::select("SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ?", [$table]);
                foreach ($rawIndexes as $row) {
                    $existingIndexes[$row->name] = true;
                }
            } else {
                $rawIndexes = DB::select("SHOW INDEX FROM {$table}");
                foreach ($rawIndexes as $row) {
                    $existingIndexes[$row->Key_name] = true;
                }
            }
        } catch (\Exception $e) {
            echo "Could not check existing indexes for {$table}: " . $e->getMessage() . "\n";
        }

        foreach ($indexes as $index) {
            $indexName = $index['name'];
            $columns = $index['columns'];
            
            if (!empty($existingIndexes[$indexName])) {
                echo "Index {$indexName} already exists on {$table}, skipping...\n";
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                    $table->index($columns, $indexName);
                });
                echo "Created index {$indexName} on {$table}\n";
            } catch (\Exception $e) {
                echo "Failed to create index {$indexName} on {$table}: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Safely drop indexes from a table
     */
    private function safeDropIndexes(string $table, array $indexNames): void
    {
        // Check if table exists
        if (!Schema::hasTable($table)) {
            echo "Skipping index deletion for non-existent table: {$table}\n";
            return;
        }

        foreach ($indexNames as $indexName) {
            try {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
                echo "Dropped index {$indexName} from {$table}\n";
            } catch (\Exception $e) {
                echo "Failed to drop index {$indexName} from {$table}: " . $e->getMessage() . "\n";
            }
        }
    }
};