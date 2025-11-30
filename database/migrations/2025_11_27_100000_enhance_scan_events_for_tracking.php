<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_events', function (Blueprint $table) {
            // GPS coordinates (explicit columns for easier querying)
            if (!Schema::hasColumn('scan_events', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('geojson');
            }
            if (!Schema::hasColumn('scan_events', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('scan_events', 'gps_accuracy')) {
                $table->decimal('gps_accuracy', 8, 2)->nullable()->after('longitude')->comment('Accuracy in meters');
            }
            
            // POD (Proof of Delivery) fields
            if (!Schema::hasColumn('scan_events', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('gps_accuracy');
            }
            if (!Schema::hasColumn('scan_events', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('photo_path');
            }
            if (!Schema::hasColumn('scan_events', 'recipient_name')) {
                $table->string('recipient_name')->nullable()->after('signature_path');
            }
            if (!Schema::hasColumn('scan_events', 'recipient_id_type')) {
                $table->string('recipient_id_type', 50)->nullable()->after('recipient_name');
            }
            if (!Schema::hasColumn('scan_events', 'recipient_id_number')) {
                $table->string('recipient_id_number')->nullable()->after('recipient_id_type');
            }
            
            // Validation and fraud detection
            if (!Schema::hasColumn('scan_events', 'is_validated')) {
                $table->boolean('is_validated')->default(false)->after('recipient_id_number');
            }
            if (!Schema::hasColumn('scan_events', 'validation_errors')) {
                $table->json('validation_errors')->nullable()->after('is_validated');
            }
            if (!Schema::hasColumn('scan_events', 'distance_from_expected')) {
                $table->decimal('distance_from_expected', 10, 2)->nullable()->after('validation_errors')->comment('Distance in meters from expected location');
            }
            
            // Geofencing
            if (!Schema::hasColumn('scan_events', 'geofence_id')) {
                $table->unsignedBigInteger('geofence_id')->nullable()->after('distance_from_expected');
            }
            if (!Schema::hasColumn('scan_events', 'is_within_geofence')) {
                $table->boolean('is_within_geofence')->nullable()->after('geofence_id');
            }
            
            // Device information
            if (!Schema::hasColumn('scan_events', 'device_id')) {
                $table->string('device_id')->nullable()->after('is_within_geofence');
            }
            if (!Schema::hasColumn('scan_events', 'device_info')) {
                $table->json('device_info')->nullable()->after('device_id');
            }
            
            // Add indexes
            $table->index(['latitude', 'longitude'], 'scan_events_coordinates_index');
            $table->index('is_validated', 'scan_events_validated_index');
        });
    }

    public function down(): void
    {
        Schema::table('scan_events', function (Blueprint $table) {
            $columns = [
                'latitude', 'longitude', 'gps_accuracy', 'photo_path', 'signature_path',
                'recipient_name', 'recipient_id_type', 'recipient_id_number',
                'is_validated', 'validation_errors', 'distance_from_expected',
                'geofence_id', 'is_within_geofence', 'device_id', 'device_info'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('scan_events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
