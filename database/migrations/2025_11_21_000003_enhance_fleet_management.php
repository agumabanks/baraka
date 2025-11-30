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
        // Enhance manifests for internal fleet
        Schema::table('manifests', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('carrier_id')->constrained('drivers')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->after('driver_id')->constrained('vehicles')->nullOnDelete();
            $table->string('type')->default('INTERNAL')->after('mode'); // INTERNAL, 3PL
        });

        // Create manifest items (polymorphic for Shipments and Consolidations)
        Schema::create('manifest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifest_id')->constrained('manifests')->cascadeOnDelete();
            $table->morphs('manifestable'); // shipment_id/consolidation_id
            $table->timestamp('loaded_at')->nullable();
            $table->timestamp('unloaded_at')->nullable();
            $table->string('status')->default('LOADED'); // LOADED, UNLOADED, MISSING, DAMAGED
            $table->timestamps();

            $table->unique(['manifest_id', 'manifestable_id', 'manifestable_type'], 'manifest_item_unique');
        });

        // Add tracking to vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('current_branch_id')->nullable()->after('branch_id')->constrained('branches')->nullOnDelete();
            $table->timestamp('last_location_update')->nullable()->after('current_branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['current_branch_id']);
            $table->dropColumn(['current_branch_id', 'last_location_update']);
        });

        Schema::dropIfExists('manifest_items');

        Schema::table('manifests', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn(['driver_id', 'vehicle_id', 'type']);
        });
    }
};
