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
        Schema::table('hubs', function (Blueprint $table) {
            // Branch identification and hierarchy
            $table->string('branch_code', 10)->unique()->after('id');
            $table->string('branch_type')->default('regional')->after('branch_code'); // regional, local, distribution
            $table->foreignId('parent_hub_id')->nullable()->constrained('hubs')->onDelete('set null')->after('branch_type');

            // Operational details
            $table->string('manager_name')->nullable()->after('name');
            $table->string('manager_email')->nullable()->after('manager_name');
            $table->string('manager_phone')->nullable()->after('manager_email');
            $table->time('operating_hours_start')->default('08:00:00')->after('phone');
            $table->time('operating_hours_end')->default('18:00:00')->after('operating_hours_start');

            // Capacity and performance metrics
            $table->integer('max_daily_capacity')->default(1000)->after('current_balance');
            $table->integer('current_daily_load')->default(0)->after('max_daily_capacity');
            $table->decimal('performance_rating', 3, 2)->default(0.00)->after('current_daily_load');

            // Service coverage
            $table->json('service_areas')->nullable()->after('hub_long'); // Areas this hub serves
            $table->json('supported_services')->nullable()->after('service_areas'); // Services offered

            // Financial tracking
            $table->decimal('monthly_budget', 15, 2)->default(0.00)->after('current_balance');
            $table->decimal('monthly_expenses', 15, 2)->default(0.00)->after('monthly_budget');

            // Compliance and certifications
            $table->json('certifications')->nullable()->after('monthly_expenses'); // ISO, safety certifications
            $table->date('last_audit_date')->nullable()->after('certifications');
            $table->string('audit_status')->default('pending')->after('last_audit_date');

            // Contact information
            $table->string('emergency_contact_name')->nullable()->after('phone');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');

            // Technology integration
            $table->boolean('has_automated_sorting')->default(false)->after('emergency_contact_phone');
            $table->boolean('has_tracking_system')->default(true)->after('has_automated_sorting');
            $table->boolean('has_security_system')->default(true)->after('has_tracking_system');

            // KPIs and metrics
            $table->json('kpi_targets')->nullable()->after('has_security_system');
            $table->json('performance_metrics')->nullable()->after('kpi_targets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hubs', function (Blueprint $table) {
            $table->dropForeign(['parent_hub_id']);
            $table->dropColumn([
                'branch_code',
                'branch_type',
                'parent_hub_id',
                'manager_name',
                'manager_email',
                'manager_phone',
                'operating_hours_start',
                'operating_hours_end',
                'max_daily_capacity',
                'current_daily_load',
                'performance_rating',
                'service_areas',
                'supported_services',
                'monthly_budget',
                'monthly_expenses',
                'certifications',
                'last_audit_date',
                'audit_status',
                'emergency_contact_name',
                'emergency_contact_phone',
                'has_automated_sorting',
                'has_tracking_system',
                'has_security_system',
                'kpi_targets',
                'performance_metrics'
            ]);
        });
    }
};
