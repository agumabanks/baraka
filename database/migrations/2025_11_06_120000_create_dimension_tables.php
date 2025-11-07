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

        // Time Dimension Table
        Schema::create('dim_time', function (Blueprint $table) {
            $table->integer('date_key')->primary();
            $table->date('full_date')->unique();
            $table->tinyInteger('day_of_week');
            $table->string('day_name', 10);
            $table->tinyInteger('day_of_month');
            $table->smallInteger('day_of_year');
            $table->tinyInteger('week_of_year');
            $table->tinyInteger('month_number');
            $table->string('month_name', 10);
            $table->tinyInteger('quarter_number');
            $table->string('quarter_name', 10);
            $table->smallInteger('year_number');
            $table->boolean('is_weekend');
            $table->boolean('is_holiday');
            $table->smallInteger('fiscal_year');
            $table->tinyInteger('fiscal_quarter');
            
            $table->index(['year_number', 'quarter_number']);
            $table->index(['month_number', 'year_number']);
        });

        // Client Dimension Table
        Schema::create('dim_client', function (Blueprint $table) {
            $table->bigIncrements('client_key');
            $table->bigInteger('client_id');
            $table->string('client_code', 20)->unique();
            $table->string('business_name', 200);
            $table->string('industry', 100)->nullable();
            $table->enum('client_tier', ['ENTERPRISE', 'STANDARD', 'BASIC'])->default('STANDARD');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            
            // Contact information
            $table->string('primary_contact_name', 100)->nullable();
            $table->string('primary_contact_email', 100)->nullable();
            $table->string('primary_contact_phone', 20)->nullable();
            
            // Service level
            $table->string('service_level_agreement', 50)->nullable();
            $table->tinyInteger('priority_level')->default(3);
            
            // Financial terms
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->tinyInteger('payment_terms_days')->default(30);
            
            // Status tracking
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->default(now()->toDateString());
            $table->date('expiration_date')->default('9999-12-31');
            
            // ETL metadata
            $table->string('etl_batch_id', 50)->nullable();
            $table->string('source_system', 50)->nullable();
            $table->timestamp('last_updated_timestamp')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('client_code');
            $table->index(['client_tier', 'service_level_agreement']);
        });

        // Branch Dimension Table
        Schema::create('dim_branch', function (Blueprint $table) {
            $table->bigIncrements('branch_key');
            $table->bigInteger('branch_id');
            $table->string('branch_code', 20)->unique();
            $table->string('branch_name', 200);
            $table->enum('branch_type', ['HUB', 'REGIONAL', 'LOCAL'])->default('LOCAL');
            $table->boolean('is_hub')->default(false);
            $table->bigInteger('parent_branch_key')->nullable();
            
            // Location details
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Contact information
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('manager_name', 100)->nullable();
            
            // Operational details
            $table->integer('capacity_shipments_per_day')->default(1000);
            $table->json('operating_hours')->nullable();
            $table->json('service_capabilities')->nullable();
            
            // Performance targets
            $table->decimal('on_time_delivery_target', 5, 2)->default(95.00);
            $table->decimal('customer_satisfaction_target', 3, 2)->default(4.50);
            
            // Status tracking
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->default(now()->toDateString());
            $table->date('expiration_date')->default('9999-12-31');
            
            $table->index('branch_type');
            $table->index(['latitude', 'longitude']);
            $table->index(['is_hub', 'is_active']);
        });

        // Customer Dimension Table
        Schema::create('dim_customer', function (Blueprint $table) {
            $table->bigIncrements('customer_key');
            $table->bigInteger('customer_id');
            $table->string('customer_code', 20)->unique()->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('full_name', 200);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            
            // Address information
            $table->text('street_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Customer segmentation
            $table->enum('customer_tier', ['VIP', 'PREMIUM', 'STANDARD', 'BASIC'])->default('STANDARD');
            $table->string('acquisition_channel', 50)->nullable();
            $table->date('customer_since_date')->nullable();
            
            // Behavioral metrics
            $table->integer('total_shipments')->default(0);
            $table->decimal('total_spend', 12, 2)->default(0.00);
            $table->decimal('average_order_value', 10, 2)->default(0.00);
            $table->date('last_shipment_date')->nullable();
            
            // Preferences
            $table->string('preferred_delivery_time', 50)->nullable();
            $table->json('notification_preferences')->nullable();
            
            // Status tracking
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->default(now()->toDateString());
            $table->date('expiration_date')->default('9999-12-31');
            
            $table->index('customer_tier');
            $table->index(['latitude', 'longitude']);
            $table->index(['total_spend', 'average_order_value']);
        });

        // Carrier Dimension Table
        Schema::create('dim_carrier', function (Blueprint $table) {
            $table->bigIncrements('carrier_key');
            $table->bigInteger('carrier_id');
            $table->string('carrier_code', 20)->unique();
            $table->string('carrier_name', 200);
            $table->enum('carrier_type', ['INTERNAL', 'EXTERNAL_PARTNER', 'THIRD_PARTY'])->default('INTERNAL');
            $table->json('service_modes')->nullable();
            
            // Performance metrics
            $table->decimal('on_time_performance', 5, 2)->nullable();
            $table->decimal('cost_per_km', 8, 4)->nullable();
            $table->decimal('capacity_utilization', 5, 2)->nullable();
            
            // Contract terms
            $table->decimal('contract_rate', 8, 4)->nullable();
            $table->decimal('fuel_surcharge_rate', 5, 4)->nullable();
            $table->decimal('minimum_charge', 8, 2)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->default(now()->toDateString());
            $table->date('expiration_date')->default('9999-12-31');
            
            $table->index('carrier_type');
            $table->index(['on_time_performance', 'cost_per_km']);
        });

        // Driver Dimension Table
        Schema::create('dim_driver', function (Blueprint $table) {
            $table->bigIncrements('driver_key');
            $table->bigInteger('driver_id');
            $table->string('employee_id', 20)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('full_name', 200);
            $table->string('license_number', 50)->nullable();
            $table->date('license_expiry_date')->nullable();
            
            // Performance metrics
            $table->integer('total_deliveries')->default(0);
            $table->decimal('on_time_delivery_rate', 5, 2)->nullable();
            $table->decimal('customer_rating', 3, 2)->nullable();
            $table->integer('accident_count')->default(0);
            
            // Route information
            $table->bigInteger('primary_branch_key')->nullable();
            $table->json('service_areas')->nullable();
            
            // Employment details
            $table->date('hire_date')->nullable();
            $table->enum('employment_status', ['ACTIVE', 'INACTIVE', 'TERMINATED'])->default('ACTIVE');
            
            $table->index(['on_time_delivery_rate', 'customer_rating']);
            $table->index('primary_branch_key');
        });

        // Add foreign key constraints
        Schema::table('dim_branch', function (Blueprint $table) {
            $table->foreign('parent_branch_key')->references('branch_key')->on('dim_branch')->nullOnDelete();
        });

        Schema::table('dim_driver', function (Blueprint $table) {
            $table->foreign('primary_branch_key')->references('branch_key')->on('dim_branch')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('dim_time');
        Schema::dropIfExists('dim_client');
        Schema::dropIfExists('dim_branch');
        Schema::dropIfExists('dim_customer');
        Schema::dropIfExists('dim_carrier');
        Schema::dropIfExists('dim_driver');
    }
};