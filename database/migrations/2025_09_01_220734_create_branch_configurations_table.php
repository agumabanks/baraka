<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branch_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained('hubs')->onDelete('cascade');

            // Service configurations
            $table->json('delivery_charge_rules')->nullable(); // Branch-specific pricing
            $table->json('service_restrictions')->nullable(); // Services not available at this branch
            $table->json('operating_restrictions')->nullable(); // Time-based restrictions

            // Inventory management
            $table->integer('max_inventory_capacity')->default(10000);
            $table->integer('current_inventory_count')->default(0);
            $table->json('inventory_alert_thresholds')->nullable();

            // Staff management
            $table->integer('max_staff_capacity')->default(50);
            $table->integer('current_staff_count')->default(0);
            $table->json('staff_shift_schedules')->nullable();

            // Equipment and assets
            $table->json('equipment_inventory')->nullable();
            $table->json('vehicle_fleet')->nullable();
            $table->boolean('automated_sorting_enabled')->default(false);

            // Financial configurations
            $table->decimal('daily_budget_limit', 12, 2)->default(50000.00);
            $table->decimal('monthly_budget_limit', 15, 2)->default(1000000.00);
            $table->json('payment_methods_supported')->nullable();

            // Compliance settings
            $table->json('compliance_requirements')->nullable();
            $table->date('next_safety_audit')->nullable();
            $table->date('next_compliance_review')->nullable();

            // Performance targets
            $table->json('kpi_targets')->nullable();
            $table->decimal('target_on_time_delivery_rate', 5, 2)->default(95.00);
            $table->decimal('target_customer_satisfaction', 5, 2)->default(4.50);

            // Communication settings
            $table->json('notification_preferences')->nullable();
            $table->string('branch_email')->nullable();
            $table->string('branch_phone')->nullable();

            // Emergency protocols
            $table->json('emergency_contacts')->nullable();
            $table->text('emergency_procedures')->nullable();

            // Integration settings
            $table->json('api_endpoints')->nullable();
            $table->json('third_party_integrations')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['hub_id']);
            $table->index(['next_safety_audit']);
            $table->index(['next_compliance_review']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_configurations');
    }
};
