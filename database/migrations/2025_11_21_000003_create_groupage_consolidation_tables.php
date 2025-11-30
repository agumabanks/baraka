<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for groupage consolidation (mother/baby shipment architecture)
     * Supports both physical (BBX) and virtual (LBX) consolidation models
     */
    public function up(): void
    {
        // Consolidations table (Mother shipments)
        Schema::create('consolidations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id'); // Origin branch
            $table->string('consolidation_number')->unique(); // e.g., CONS-2025-001
            $table->enum('type', ['BBX', 'LBX'])->default('BBX'); // BBX = physical, LBX = virtual
            $table->string('destination'); // Destination country/city/facility
            $table->unsignedBigInteger('destination_branch_id')->nullable();
            
            // Status workflow
            $table->enum('status', [
                'OPEN',           // Accepting shipments
                'LOCKED',         // No more additions
                'IN_TRANSIT',     // Dispatched
                'ARRIVED',        // Reached destination
                'DECONSOLIDATING', // Being unpacked
                'COMPLETED',      // All babies released
                'CANCELLED'
            ])->default('OPEN');
            
            // Consolidation rules and metadata
            $table->integer('max_pieces')->nullable(); // Maximum number of baby shipments
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->decimal('max_volume_cbm', 10, 3)->nullable();
            $table->timestamp('cutoff_time')->nullable(); // Deadline for adding shipments
            
            // Current state
            $table->integer('current_pieces')->default(0);
            $table->decimal('current_weight_kg', 10, 2)->default(0);
            $table->decimal('current_volume_cbm', 10, 3)->default(0);
            
            // Transport details
            $table->string('transport_mode')->nullable(); // AIR, SEA, ROAD, RAIL
            $table->string('awb_number')->nullable(); // Air waybill or master transport doc
            $table->string('container_number')->nullable(); // For sea freight
            $table->string('vehicle_number')->nullable(); // For road transport
            
            // Timestamps
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('deconsolidation_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Users
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();
            
            $table->json('metadata')->nullable(); // Additional consolidation data
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('branch_id');
            $table->index('destination_branch_id');
            $table->index('status');
            $table->index(['type', 'status']);
            $table->index('consolidation_number');
            $table->index('cutoff_time');
            
            // Foreign keys
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('destination_branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('locked_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('dispatched_by')->references('id')->on('users')->nullOnDelete();
        });

        // Consolidation Shipments (Pivot table linking baby shipments to mother consolidation)
        Schema::create('consolidation_shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consolidation_id');
            $table->unsignedBigInteger('shipment_id');
            
            $table->integer('sequence_number')->nullable(); // Order within consolidation
            $table->decimal('weight_kg', 10, 2);
            $table->decimal('volume_cbm', 10, 3)->nullable();
            
            $table->timestamp('added_at')->useCurrent();
            $table->timestamp('removed_at')->nullable(); // If removed before lock
            $table->unsignedBigInteger('added_by')->nullable();
            
            $table->enum('status', [
                'ADDED',
                'LOCKED',
                'IN_TRANSIT',
                'DECONSOLIDATED',
                'REMOVED'
            ])->default('ADDED');
            
            $table->timestamps();

            // Ensure shipment is only in one active consolidation
            $table->unique(['shipment_id', 'consolidation_id']);
            $table->index(['consolidation_id', 'status']);
            $table->index('shipment_id');
            
            $table->foreign('consolidation_id')->references('id')->on('consolidations')->cascadeOnDelete();
            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->foreign('added_by')->references('id')->on('users')->nullOnDelete();
        });

        // Consolidation Rules (Auto-consolidation logic)
        Schema::create('consolidation_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            
            $table->string('rule_name');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100); // Lower = higher priority
            
            // Matching criteria
            $table->string('destination_country')->nullable();
            $table->string('destination_city')->nullable();
            $table->unsignedBigInteger('destination_branch_id')->nullable();
            $table->string('service_level')->nullable();
            
            // Consolidation parameters
            $table->enum('consolidation_type', ['BBX', 'LBX'])->default('BBX');
            $table->integer('min_pieces')->default(3); // Minimum shipments to create consolidation
            $table->integer('max_pieces')->default(100);
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->integer('max_age_hours')->default(24); // Max time to wait before dispatching
            
            // Schedule
            $table->json('schedule')->nullable(); // e.g., {"monday": "18:00", "friday": "18:00"}
            $table->time('default_cutoff_time')->nullable();
            
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
            $table->index('priority');
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('destination_branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        // Deconsolidation Events (Unpacking records)
        Schema::create('deconsolidation_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consolidation_id');
            $table->unsignedBigInteger('shipment_id'); // Baby shipment being released
            $table->unsignedBigInteger('branch_id'); // Where deconsolidation occurred
            
            $table->enum('event_type', [
                'STARTED',
                'SHIPMENT_SCANNED',
                'SHIPMENT_RELEASED',
                'DISCREPANCY',
                'COMPLETED'
            ]);
            
            $table->text('notes')->nullable();
            $table->json('discrepancy_data')->nullable(); // For missing/damaged items
            $table->unsignedBigInteger('performed_by');
            $table->timestamp('occurred_at')->useCurrent();
            
            $table->timestamps();

            $table->index(['consolidation_id', 'event_type']);
            $table->index('shipment_id');
            $table->index('occurred_at');
            
            $table->foreign('consolidation_id')->references('id')->on('consolidations')->cascadeOnDelete();
            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->cascadeOnDelete();
        });

        // Modify shipments table to support consolidation
        if (Schema::hasTable('shipments')) {
            Schema::table('shipments', function (Blueprint $table) {
                if (!Schema::hasColumn('shipments', 'is_consolidation')) {
                    $table->boolean('is_consolidation')->default(false)->after('tracking_number');
                }
                if (!Schema::hasColumn('shipments', 'consolidation_id')) {
                    $table->unsignedBigInteger('consolidation_id')->nullable()->after('is_consolidation');
                    $table->index('consolidation_id');
                    $table->foreign('consolidation_id')->references('id')->on('consolidations')->nullOnDelete();
                }
                if (!Schema::hasColumn('shipments', 'consolidation_type')) {
                    $table->enum('consolidation_type', ['individual', 'BBX', 'LBX'])->default('individual')->after('consolidation_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove consolidation columns from shipments
        if (Schema::hasTable('shipments')) {
            Schema::table('shipments', function (Blueprint $table) {
                if (Schema::hasColumn('shipments', 'consolidation_id')) {
                    $table->dropForeign(['consolidation_id']);
                    $table->dropIndex(['consolidation_id']);
                    $table->dropColumn(['consolidation_id', 'is_consolidation', 'consolidation_type']);
                }
            });
        }

        Schema::dropIfExists('deconsolidation_events');
        Schema::dropIfExists('consolidation_rules');
        Schema::dropIfExists('consolidation_shipments');
        Schema::dropIfExists('consolidations');
    }
};
