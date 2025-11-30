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
        // Vehicle Trips
        Schema::create('vehicle_trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number', 50)->unique();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('branch_id');
            
            // Route information
            $table->unsignedBigInteger('origin_branch_id');
            $table->unsignedBigInteger('destination_branch_id')->nullable();
            $table->string('trip_type', 30); // delivery, pickup, transfer, route
            $table->string('route_name', 100)->nullable();
            
            // Status and timing
            $table->string('status', 30)->default('planned'); // planned, in_progress, completed, cancelled
            $table->dateTime('planned_start_at');
            $table->dateTime('planned_end_at')->nullable();
            $table->dateTime('actual_start_at')->nullable();
            $table->dateTime('actual_end_at')->nullable();
            
            // Metrics
            $table->decimal('planned_distance_km', 10, 2)->nullable();
            $table->decimal('actual_distance_km', 10, 2)->nullable();
            $table->decimal('fuel_consumption_liters', 10, 2)->nullable();
            $table->integer('total_stops')->default(0);
            $table->integer('completed_stops')->default(0);
            
            // Cargo
            $table->integer('shipment_count')->default(0);
            $table->decimal('total_weight_kg', 10, 2)->nullable();
            $table->json('cargo_manifest')->nullable();
            
            // Additional data
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('restrict');
            // Branch foreign keys removed - will add manually if needed
            
            $table->index('status');
            $table->index('trip_type');
            $table->index(['branch_id', 'status']);
            $table->index('planned_start_at');
        });

        // Trip Stops (waypoints/delivery stops)
        Schema::create('trip_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->integer('sequence')->default(0);
            
            // Stop details
            $table->string('stop_type', 30); // pickup, delivery, waypoint
            $table->string('location_name', 200);
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Contact
            $table->string('contact_person', 100)->nullable();
            $table->string('contact_phone', 50)->nullable();
            
            // Status and timing
            $table->string('status', 30)->default('pending'); // pending, in_transit, arrived, completed, failed
            $table->dateTime('planned_arrival')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->dateTime('completed_at')->nullable();
            
            // Shipments at this stop
            $table->json('shipment_ids')->nullable();
            $table->integer('items_count')->default(0);
            
            // POD (Proof of Delivery)
            $table->string('recipient_name')->nullable();
            $table->string('recipient_signature_path')->nullable();
            $table->json('photo_paths')->nullable();
            $table->text('delivery_notes')->nullable();
            
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->foreign('trip_id')->references('id')->on('vehicle_trips')->onDelete('cascade');
            $table->index(['trip_id', 'sequence']);
            $table->index('status');
        });

        // Vehicle Maintenance Records
        Schema::create('vehicle_maintenance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('reported_by_user_id');
            $table->unsignedBigInteger('performed_by_user_id')->nullable();
            
            // Maintenance details
            $table->string('maintenance_type', 50); // routine, repair, inspection, emergency
            $table->string('category', 50)->nullable(); // engine, brakes, tires, electrical, body, etc.
            $table->string('status', 30)->default('scheduled'); // scheduled, in_progress, completed, cancelled
            
            $table->text('description');
            $table->text('work_performed')->nullable();
            
            // Scheduling
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            
            // Odometer
            $table->integer('odometer_reading')->nullable();
            $table->integer('next_service_at')->nullable();
            
            // Cost
            $table->decimal('parts_cost', 10, 2)->default(0);
            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->string('invoice_number', 50)->nullable();
            
            // Service provider
            $table->string('service_provider', 200)->nullable();
            $table->string('mechanic_name', 100)->nullable();
            
            // Priority
            $table->string('priority', 20)->default('normal'); // low, normal, high, critical
            
            $table->text('notes')->nullable();
            $table->json('parts_used')->nullable();
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            // Branch foreign key removed - will add manually if needed
            $table->foreign('reported_by_user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('performed_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index('status');
            $table->index('maintenance_type');
            $table->index(['vehicle_id', 'status']);
            $table->index('scheduled_at');
        });

        // Vehicle Service Intervals (preventive maintenance schedules)
        Schema::create('vehicle_service_intervals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            
            $table->string('service_type', 50); // oil_change, tire_rotation, brake_inspection, etc.
            $table->integer('interval_km')->nullable(); // Service every X km
            $table->integer('interval_months')->nullable(); // Service every X months
            
            $table->integer('last_service_km')->nullable();
            $table->date('last_service_date')->nullable();
            
            $table->integer('next_service_km')->nullable();
            $table->date('next_service_date')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->index(['vehicle_id', 'is_active']);
        });

        // Add maintenance fields to vehicles table
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'current_odometer')) {
                $table->integer('current_odometer')->nullable()->after('status');
            }
            if (!Schema::hasColumn('vehicles', 'last_maintenance_at')) {
                $table->date('last_maintenance_at')->nullable()->after('current_odometer');
            }
            if (!Schema::hasColumn('vehicles', 'next_maintenance_due')) {
                $table->date('next_maintenance_due')->nullable()->after('last_maintenance_at');
            }
            if (!Schema::hasColumn('vehicles', 'maintenance_status')) {
                $table->string('maintenance_status', 30)->default('good')->after('next_maintenance_due'); // good, due_soon, overdue, critical
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['current_odometer', 'last_maintenance_at', 'next_maintenance_due', 'maintenance_status']);
        });
        
        Schema::dropIfExists('vehicle_service_intervals');
        Schema::dropIfExists('vehicle_maintenance');
        Schema::dropIfExists('trip_stops');
        Schema::dropIfExists('vehicle_trips');
    }
};
