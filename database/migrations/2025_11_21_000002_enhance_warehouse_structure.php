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
        // Enhance wh_locations for hierarchy
        Schema::table('wh_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('wh_locations', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('branch_id')->constrained('wh_locations')->nullOnDelete();
            }
            if (! Schema::hasColumn('wh_locations', 'barcode')) {
                $table->string('barcode')->nullable()->unique()->after('code');
            }
            if (! Schema::hasColumn('wh_locations', 'level')) {
                $table->integer('level')->default(0)->after('type')->comment('0=Zone, 1=Aisle, 2=Rack, 3=Shelf, 4=Bin');
            }
            if (! Schema::hasColumn('wh_locations', 'meta_data')) {
                $table->json('meta_data')->nullable()->after('status');
            }
        });

        // Add location tracking to parcels
        Schema::table('parcels', function (Blueprint $table) {
            if (Schema::hasColumn('parcels', 'current_location_id')) {
                return;
            }

            $column = $table->foreignId('current_location_id')->nullable()->constrained('wh_locations')->nullOnDelete();

            // Place column after a sensible existing column when possible
            if (Schema::hasColumn('parcels', 'shipment_id')) {
                $column->after('shipment_id');
            } elseif (Schema::hasColumn('parcels', 'merchant_id')) {
                $column->after('merchant_id');
            }
        });

        // Create warehouse movements log
        Schema::create('warehouse_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained('parcels')->cascadeOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('wh_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('wh_locations')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('type')->default('MOVE'); // MOVE, PUTAWAY, PICK, ADJUSTMENT
            $table->string('reference')->nullable(); // e.g., Shipment ID, Order ID
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['parcel_id', 'created_at']);
            $table->index(['to_location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_movements');

        Schema::table('parcels', function (Blueprint $table) {
            if (Schema::hasColumn('parcels', 'current_location_id')) {
                $table->dropForeign(['current_location_id']);
                $table->dropColumn('current_location_id');
            }
        });

        Schema::table('wh_locations', function (Blueprint $table) {
            if (Schema::hasColumn('wh_locations', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            foreach (['barcode', 'level', 'meta_data'] as $column) {
                if (Schema::hasColumn('wh_locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
