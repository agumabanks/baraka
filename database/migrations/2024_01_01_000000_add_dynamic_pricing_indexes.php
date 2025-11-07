<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database optimization for Dynamic Pricing System
 * 
 * This migration adds proper indexes to optimize quote calculations,
 * customer lookups, and competitor pricing queries.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Quotation table optimizations
        Schema::table('quotations', function (Blueprint $table) {
            $table->index(['customer_id', 'status'], 'idx_quotation_customer_status');
            $table->index(['origin_branch_id', 'destination_country'], 'idx_quotation_route');
            $table->index(['service_type', 'created_at'], 'idx_quotation_service_date');
            $table->index(['valid_until', 'status'], 'idx_quotation_validity');
            $table->index(['customer_id', 'created_at'], 'idx_quotation_customer_date');
        });

        // RateCard table optimizations
        Schema::table('rate_cards', function (Blueprint $table) {
            $table->index(['origin_country', 'dest_country', 'is_active'], 'idx_ratecard_route_active');
            $table->index(['is_active', 'created_at'], 'idx_ratecard_active_date');
        });

        // PricingRule table optimizations
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->index(['rule_type', 'active', 'priority'], 'idx_pricingrule_type_active_priority');
            $table->index(['active', 'effective_from', 'effective_to'], 'idx_pricingrule_validity');
        });

        // CompetitorPrice table optimizations
        Schema::table('competitor_prices', function (Blueprint $table) {
            $table->index(['origin_country', 'destination_country', 'service_level'], 'idx_competitor_route_service');
            $table->index(['carrier_name', 'collected_at'], 'idx_competitor_carrier_date');
            $table->index(['source_type', 'collected_at'], 'idx_competitor_source_date');
        });

        // FuelIndex table optimizations
        Schema::table('fuel_indices', function (Blueprint $table) {
            $table->index(['source', 'region', 'effective_date'], 'idx_fuel_source_region_date');
            $table->index(['effective_date'], 'idx_fuel_effective_date');
        });

        // ServiceLevelDefinition table optimizations
        Schema::table('service_level_definitions', function (Blueprint $table) {
            $table->index(['code'], 'idx_servicelevel_code');
        });

        // SurchargeRule table optimizations
        Schema::table('surcharge_rules', function (Blueprint $table) {
            $table->index(['code', 'active'], 'idx_surcharge_code_active');
            $table->index(['active_from', 'active_to'], 'idx_surcharge_validity');
        });

        // Zone table optimizations
        Schema::table('zones', function (Blueprint $table) {
            $table->index(['code'], 'idx_zone_code');
        });

        // Customers table optimizations for pricing
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['customer_type', 'status'], 'idx_customer_type_status');
            $table->index(['total_shipments', 'total_spent'], 'idx_customer_volume_value');
            $table->index(['priority_level', 'customer_since'], 'idx_customer_priority_since');
        });

        // Shipments table optimizations
        Schema::table('shipments', function (Blueprint $table) {
            $table->index(['customer_id', 'service_level', 'current_status'], 'idx_shipment_customer_service_status');
            $table->index(['origin_branch_id', 'dest_branch_id', 'current_status'], 'idx_shipment_route_status');
            $table->index(['service_level', 'created_at'], 'idx_shipment_service_date');
        });

        // Create composite index for frequently joined queries
        Schema::table('quotations', function (Blueprint $table) {
            $table->index(['customer_id', 'service_type', 'created_at'], 'idx_quotation_customer_service_date');
        });

        // Create partial indexes for specific conditions
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->index(['rule_type', 'active'])
                  ->where('active', true);
        });

        Schema::table('rate_cards', function (Blueprint $table) {
            $table->index(['origin_country', 'dest_country'])
                  ->where('is_active', true);
        });

        // Create covering index for quote history queries
        Schema::table('quotations', function (Blueprint $table) {
            $table->index(['customer_id', 'created_at', 'total_amount', 'service_type'], 'idx_quotation_customer_history');
        });

        // Create index for competitor pricing benchmarking
        Schema::table('competitor_prices', function (Blueprint $table) {
            $table->index(['origin_country', 'destination_country', 'service_level', 'collected_at'], 'idx_competitor_benchmarking');
        });

        // Create index for fuel surcharge calculations
        Schema::table('fuel_indices', function (Blueprint $table) {
            $table->index(['source', 'effective_date'], 'idx_fuel_surcharge_calc');
        });

        // Create index for dimensional weight calculations
        Schema::table('shipments', function (Blueprint $table) {
            $table->index(['service_level', 'created_at'], 'idx_shipment_dimensional_calc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('idx_shipment_dimensional_calc');
        });

        Schema::table('fuel_indices', function (Blueprint $table) {
            $table->dropIndex('idx_fuel_surcharge_calc');
        });

        Schema::table('competitor_prices', function (Blueprint $table) {
            $table->dropIndex('idx_competitor_benchmarking');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('idx_quotation_customer_history');
        });

        Schema::table('rate_cards', function (Blueprint $table) {
            $table->dropIndex(['origin_country', 'dest_country'])
                  ->where('is_active', true);
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropIndex(['rule_type', 'active'])
                  ->where('active', true);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('idx_quotation_customer_service_date');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('idx_shipment_service_date');
            $table->dropIndex('idx_shipment_route_status');
            $table->dropIndex('idx_shipment_customer_service_status');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customer_priority_since');
            $table->dropIndex('idx_customer_volume_value');
            $table->dropIndex('idx_customer_type_status');
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->dropIndex('idx_zone_code');
        });

        Schema::table('surcharge_rules', function (Blueprint $table) {
            $table->dropIndex('idx_surcharge_validity');
            $table->dropIndex('idx_surcharge_code_active');
        });

        Schema::table('service_level_definitions', function (Blueprint $table) {
            $table->dropIndex('idx_servicelevel_code');
        });

        Schema::table('fuel_indices', function (Blueprint $table) {
            $table->dropIndex('idx_fuel_effective_date');
            $table->dropIndex('idx_fuel_source_region_date');
        });

        Schema::table('competitor_prices', function (Blueprint $table) {
            $table->dropIndex('idx_competitor_source_date');
            $table->dropIndex('idx_competitor_carrier_date');
            $table->dropIndex('idx_competitor_route_service');
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropIndex('idx_pricingrule_validity');
            $table->dropIndex('idx_pricingrule_type_active_priority');
        });

        Schema::table('rate_cards', function (Blueprint $table) {
            $table->dropIndex('idx_ratecard_active_date');
            $table->dropIndex('idx_ratecard_route_active');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('idx_quotation_customer_date');
            $table->dropIndex('idx_quotation_validity');
            $table->dropIndex('idx_quotation_service_date');
            $table->dropIndex('idx_quotation_route');
            $table->dropIndex('idx_quotation_customer_status');
        });
    }
};