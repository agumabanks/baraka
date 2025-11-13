<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('stg_shipments');
        Schema::dropIfExists('fact_customer_analytics');
        Schema::dropIfExists('fact_performance_metrics');
        Schema::dropIfExists('fact_financial_transactions');
        Schema::dropIfExists('fact_shipments');

        // Fact Shipments Table (main operational fact table)
        Schema::create('fact_shipments', function (Blueprint $table) {
            $table->bigIncrements('shipment_key');
            $table->unsignedBigInteger('shipment_id');
            $table->string('tracking_number', 50);
            $table->unsignedBigInteger('client_key');
            $table->unsignedBigInteger('origin_branch_key');
            $table->unsignedBigInteger('dest_branch_key');
            $table->unsignedBigInteger('carrier_key')->nullable();
            $table->unsignedBigInteger('driver_key')->nullable();
            $table->unsignedBigInteger('customer_key');
            
            // Status and dates
            $table->string('status', 50)->default('created');
            $table->string('current_status', 50)->default('CREATED');
            
            // Delivery metrics
            $table->integer('pickup_date_key')->nullable();
            $table->integer('delivery_date_key')->nullable();
            $table->integer('scheduled_delivery_date_key')->nullable();
            $table->integer('actual_delivery_duration_minutes')->nullable();
            $table->integer('scheduled_delivery_duration_minutes')->nullable();
            
            // Financial metrics
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->decimal('shipping_charge', 10, 2)->default(0.00);
            $table->decimal('cod_amount', 10, 2)->default(0.00);
            $table->decimal('fuel_surcharge', 10, 2)->default(0.00);
            $table->decimal('insurance_cost', 8, 2)->default(0.00);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->decimal('revenue', 10, 2)->default(0.00);
            $table->decimal('margin', 10, 2)->default(0.00);
            $table->decimal('margin_percentage', 5, 2)->nullable();
            
            // Operational metrics
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('delivery_attempts')->default(0);
            $table->boolean('exception_flag')->default(false);
            $table->boolean('returned_flag')->default(false);
            $table->string('exception_reason', 100)->nullable();
            
            // Time dimensions
            $table->integer('created_date_key');
            $table->timestamp('created_timestamp')->useCurrent();
            $table->timestamp('updated_timestamp')->useCurrent()->useCurrentOnUpdate();
            
            // ETL metadata
            $table->string('etl_batch_id', 50)->nullable();
            $table->string('source_system', 50)->nullable();
            $table->decimal('data_quality_score', 3, 2)->nullable();
            $table->json('metadata')->nullable();
            
            $table->index(['client_key', 'status'], 'fact_shipments_client_status_idx');
            $table->index(['pickup_date_key', 'delivery_date_key'], 'fact_shipments_delivery_dates_idx');
            $table->index(['origin_branch_key', 'dest_branch_key'], 'fact_shipments_branch_idx');
            $table->index(['margin_percentage', 'delivery_attempts'], 'fact_shipments_margin_attempts_idx');
            $table->index(['status', 'current_status'], 'fact_shipments_status_idx');
        });

        // Fact Financial Transactions
        Schema::create('fact_financial_transactions', function (Blueprint $table) {
            $table->bigIncrements('transaction_key');
            $table->string('transaction_id', 50)->unique();
            $table->unsignedBigInteger('shipment_key')->nullable();
            $table->unsignedBigInteger('client_key')->nullable();
            $table->unsignedBigInteger('customer_key')->nullable();
            $table->unsignedBigInteger('branch_key')->nullable();
            
            // Transaction details
            $table->string('transaction_type', 50);
            $table->string('transaction_category', 50);
            $table->bigInteger('account_key')->nullable();
            
            // Financial amounts
            $table->decimal('debit_amount', 12, 2)->default(0.00);
            $table->decimal('credit_amount', 12, 2)->default(0.00);
            $table->decimal('running_balance', 12, 2)->nullable();
            
            // Time dimensions
            $table->integer('transaction_date_key');
            $table->timestamp('transaction_timestamp')->useCurrent();
            
            // Reference data
            $table->string('reference_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('POSTED');
            
            // ETL metadata
            $table->string('etl_batch_id', 50)->nullable();
            $table->string('source_system', 50)->nullable();
            
            $table->index(['client_key', 'transaction_date_key'], 'fact_fin_tx_client_date_idx');
            $table->index(['account_key', 'transaction_date_key'], 'fact_fin_tx_account_date_idx');
            $table->index('shipment_key', 'fact_fin_tx_shipment_idx');
            $table->index(['transaction_type', 'transaction_category'], 'fact_fin_tx_type_category_idx');
        });

        // Fact Performance Metrics (daily aggregates)
        Schema::create('fact_performance_metrics', function (Blueprint $table) {
            $table->bigIncrements('metric_key');
            $table->unsignedBigInteger('branch_key');
            $table->integer('date_key');
            
            // Volume metrics
            $table->integer('total_shipments')->default(0);
            $table->integer('delivered_shipments')->default(0);
            $table->integer('returned_shipments')->default(0);
            $table->integer('exception_shipments')->default(0);
            $table->integer('cancelled_shipments')->default(0);
            
            // Performance metrics
            $table->decimal('on_time_delivery_rate', 5, 2)->nullable();
            $table->decimal('first_attempt_success_rate', 5, 2)->nullable();
            $table->decimal('average_delivery_time_hours', 8, 2)->nullable();
            $table->decimal('average_delivery_attempts', 4, 2)->default(0.00);
            
            // Financial metrics
            $table->decimal('total_revenue', 12, 2)->default(0.00);
            $table->decimal('total_cost', 12, 2)->default(0.00);
            $table->decimal('total_margin', 12, 2)->default(0.00);
            $table->decimal('margin_percentage', 5, 2)->nullable();
            $table->decimal('cod_collected', 12, 2)->default(0.00);
            
            // Customer metrics
            $table->integer('customer_complaints')->default(0);
            $table->decimal('customer_satisfaction_score', 3, 2)->nullable();
            
            // Operational efficiency
            $table->decimal('vehicle_utilization_rate', 5, 2)->nullable();
            $table->decimal('driver_utilization_rate', 5, 2)->nullable();
            $table->unsignedInteger('total_distance_km')->default(0);
            $table->decimal('fuel_consumption_liters', 8, 2)->default(0.00);
            
            $table->index(['branch_key', 'date_key'], 'fact_perf_branch_date_idx');
            $table->index(['on_time_delivery_rate', 'margin_percentage'], 'fact_perf_rates_idx');
        });

        // Fact Customer Analytics
        Schema::create('fact_customer_analytics', function (Blueprint $table) {
            $table->bigIncrements('analytics_key');
            $table->unsignedBigInteger('customer_key');
            $table->integer('date_key');
            
            // Customer behavior metrics
            $table->integer('shipments_count')->default(0);
            $table->decimal('total_spend', 12, 2)->default(0.00);
            $table->decimal('average_order_value', 10, 2)->default(0.00);
            $table->decimal('average_delivery_time_hours', 8, 2)->nullable();
            
            // Frequency and recency
            $table->integer('days_since_last_shipment')->nullable();
            $table->decimal('shipment_frequency_per_month', 4, 2)->default(0.00);
            
            // Service preferences
            $table->string('preferred_service_type', 50)->nullable();
            $table->decimal('premium_service_usage_rate', 5, 2)->default(0.00);
            
            // Risk metrics
            $table->integer('complaint_count')->default(0);
            $table->decimal('customer_lifetime_value', 12, 2)->default(0.00);
            $table->decimal('churn_probability', 5, 4)->default(0.00);
            
            $table->index(['customer_key', 'date_key'], 'fact_customer_date_idx');
            $table->index(['total_spend', 'customer_lifetime_value'], 'fact_customer_value_idx');
        });

        // Staging tables for ETL processing
        Schema::create('stg_shipments', function (Blueprint $table) {
            $table->bigIncrements('stg_id');
            $table->string('stg_batch_id', 50);
            $table->timestamp('stg_created_at')->useCurrent();
            
            // Source data fields
            $table->unsignedBigInteger('shipment_id');
            $table->string('tracking_number', 50);
            $table->json('source_data');
            $table->string('source_system', 50);
            $table->string('extraction_timestamp', 50);
            
            // ETL processing status
            $table->enum('processing_status', ['PENDING', 'TRANSFORMED', 'VALIDATED', 'LOADED', 'FAILED'])->default('PENDING');
            $table->text('processing_errors')->nullable();
            $table->decimal('data_quality_score', 3, 2)->nullable();
            
            $table->index('stg_batch_id', 'stg_shipments_batch_idx');
            $table->index('processing_status', 'stg_shipments_status_idx');
        });

        // Add foreign key constraints
        Schema::table('fact_shipments', function (Blueprint $table) {
            $table->foreign('client_key')->references('client_key')->on('dim_client')->onDelete('restrict');
            $table->foreign('origin_branch_key')->references('branch_key')->on('dim_branch')->onDelete('restrict');
            $table->foreign('dest_branch_key')->references('branch_key')->on('dim_branch')->onDelete('restrict');
            $table->foreign('carrier_key')->references('carrier_key')->on('dim_carrier')->onDelete('set null');
            $table->foreign('driver_key')->references('driver_key')->on('dim_driver')->onDelete('set null');
            $table->foreign('customer_key')->references('customer_key')->on('dim_customer')->onDelete('restrict');
            $table->foreign('pickup_date_key')->references('date_key')->on('dim_time')->onDelete('restrict');
            $table->foreign('delivery_date_key')->references('date_key')->on('dim_time')->onDelete('restrict');
            $table->foreign('created_date_key')->references('date_key')->on('dim_time')->onDelete('restrict');
        });

        Schema::table('fact_financial_transactions', function (Blueprint $table) {
            $table->foreign('shipment_key')->references('shipment_key')->on('fact_shipments')->onDelete('set null');
            $table->foreign('client_key')->references('client_key')->on('dim_client')->onDelete('set null');
            $table->foreign('customer_key')->references('customer_key')->on('dim_customer')->onDelete('set null');
            $table->foreign('branch_key')->references('branch_key')->on('dim_branch')->onDelete('set null');
            $table->foreign('transaction_date_key')->references('date_key')->on('dim_time')->onDelete('restrict');
        });

        Schema::table('fact_performance_metrics', function (Blueprint $table) {
            $table->foreign('branch_key')->references('branch_key')->on('dim_branch')->onDelete('restrict');
            $table->foreign('date_key')->references('date_key')->on('dim_time')->onDelete('restrict');
        });

        Schema::table('fact_customer_analytics', function (Blueprint $table) {
            $table->foreign('customer_key')->references('customer_key')->on('dim_customer')->onDelete('restrict');
            $table->foreign('date_key')->references('date_key')->on('dim_time')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('stg_shipments');
        Schema::dropIfExists('fact_customer_analytics');
        Schema::dropIfExists('fact_performance_metrics');
        Schema::dropIfExists('fact_financial_transactions');
        Schema::dropIfExists('fact_shipments');
    }
};