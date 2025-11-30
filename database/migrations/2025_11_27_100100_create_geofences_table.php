<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('geofences')) {
            return;
        }

        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('circle'); // circle, polygon
            $table->string('entity_type'); // branch, hub, customer, zone
            $table->unsignedBigInteger('entity_id')->nullable();
            
            // For circle geofences
            $table->decimal('center_latitude', 10, 8)->nullable();
            $table->decimal('center_longitude', 11, 8)->nullable();
            $table->decimal('radius_meters', 10, 2)->default(100); // Default 100m radius
            
            // For polygon geofences
            $table->json('polygon_coordinates')->nullable(); // Array of [lat, lng] pairs
            
            // Configuration
            $table->boolean('is_active')->default(true);
            $table->string('alert_on_enter')->default('none'); // none, log, notify
            $table->string('alert_on_exit')->default('none');
            $table->boolean('require_scan_within')->default(false); // Require scans to be within geofence
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index('is_active');
            $table->index(['center_latitude', 'center_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
