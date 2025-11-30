<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hub_routes')) {
            return;
        }

        Schema::create('hub_routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('origin_hub_id');
            $table->unsignedBigInteger('destination_hub_id');
            
            // Route characteristics
            $table->decimal('distance_km', 10, 2);
            $table->integer('transit_time_hours');
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->decimal('cost_per_kg', 8, 2)->default(0);
            $table->decimal('cost_per_cbm', 8, 2)->default(0);
            
            // Service levels
            $table->string('service_level')->default('standard'); // express, standard, economy
            $table->string('transport_mode')->default('road'); // road, air, rail, sea
            
            // Scheduling
            $table->json('departure_days')->nullable(); // [1,2,3,4,5] = Mon-Fri
            $table->time('departure_time')->nullable();
            $table->time('cutoff_time')->nullable();
            
            // Capacity
            $table->integer('max_weight_kg')->nullable();
            $table->decimal('max_volume_cbm', 10, 2)->nullable();
            $table->integer('max_shipments')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            
            // Traffic/congestion factor
            $table->decimal('congestion_factor', 4, 2)->default(1.0);
            $table->timestamp('congestion_updated_at')->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('origin_hub_id')->references('id')->on('hubs')->onDelete('cascade');
            $table->foreign('destination_hub_id')->references('id')->on('hubs')->onDelete('cascade');
            
            $table->unique(['origin_hub_id', 'destination_hub_id', 'service_level'], 'hub_routes_unique');
            $table->index(['is_active', 'service_level']);
            $table->index(['transport_mode']);
        });

        // Hub capacity tracking
        Schema::create('hub_capacity_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hub_id');
            $table->date('snapshot_date');
            
            // Current utilization
            $table->integer('current_shipments')->default(0);
            $table->decimal('current_weight_kg', 12, 2)->default(0);
            $table->decimal('current_volume_cbm', 10, 2)->default(0);
            
            // Capacity limits
            $table->integer('max_shipments');
            $table->decimal('max_weight_kg', 12, 2);
            $table->decimal('max_volume_cbm', 10, 2);
            
            // Utilization percentages
            $table->decimal('shipment_utilization', 5, 2)->default(0);
            $table->decimal('weight_utilization', 5, 2)->default(0);
            $table->decimal('volume_utilization', 5, 2)->default(0);
            
            // Status
            $table->string('status')->default('normal'); // normal, warning, critical, overflow
            
            $table->timestamps();
            
            $table->foreign('hub_id')->references('id')->on('hubs')->onDelete('cascade');
            $table->unique(['hub_id', 'snapshot_date']);
            $table->index(['snapshot_date', 'status']);
        });

        // Driver/worker assignments tracking
        Schema::create('driver_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->date('assignment_date');
            
            // Assignment details
            $table->integer('assigned_shipments')->default(0);
            $table->decimal('assigned_weight_kg', 10, 2)->default(0);
            $table->decimal('assigned_distance_km', 10, 2)->default(0);
            $table->integer('estimated_duration_minutes')->default(0);
            
            // Status tracking
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Performance
            $table->integer('completed_shipments')->default(0);
            $table->integer('failed_shipments')->default(0);
            $table->decimal('actual_distance_km', 10, 2)->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            
            // Score for AI assignment
            $table->decimal('efficiency_score', 5, 2)->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('driver_id')->references('id')->on('branch_workers')->onDelete('cascade');
            $table->index(['assignment_date', 'status']);
            $table->index(['driver_id', 'assignment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_assignments');
        Schema::dropIfExists('hub_capacity_snapshots');
        Schema::dropIfExists('hub_routes');
    }
};
