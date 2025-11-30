<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aligns shipment status schema with ShipmentStatus enum:
     * - Migrates legacy lowercase 'status' column to uppercase values
     * - Makes 'current_status' the canonical status field
     * - Deprecates 'status' column (kept for backward compatibility)
     * - Ensures consistent status representation across the system
     */
    public function up(): void
    {
        if (!Schema::hasTable('shipments')) {
            return;
        }

        // Step 1: Migrate existing data from lowercase 'status' to uppercase 'current_status'
        $this->migrateLegacyStatuses();

        // Step 2: Update schema to use proper enum values
        Schema::table('shipments', function (Blueprint $table) {
            // Drop old enum constraint if it exists
            $table->string('status', 50)->change();
            
            // Update current_status to include all ShipmentStatus enum values
            $table->enum('current_status', [
                'BOOKED',
                'PICKUP_SCHEDULED',
                'PICKED_UP',
                'AT_ORIGIN_HUB',
                'BAGGED',
                'LINEHAUL_DEPARTED',
                'LINEHAUL_ARRIVED',
                'AT_DESTINATION_HUB',
                'CUSTOMS_HOLD',
                'CUSTOMS_CLEARED',
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'RETURN_INITIATED',
                'RETURN_IN_TRANSIT',
                'RETURNED',
                'CANCELLED',
                'EXCEPTION',
            ])->default('BOOKED')->change();
        });

        // Step 3: Ensure 'status' mirrors 'current_status' for backward compatibility
        DB::statement("UPDATE shipments SET status = LOWER(current_status)");

        // Step 4: Add index on current_status for performance
        Schema::table('shipments', function (Blueprint $table) {
            if (!$this->indexExists('shipments', 'shipments_current_status_index')) {
                $table->index('current_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            // Restore old enum values
            $table->enum('status', [
                'created',
                'ready_for_pickup',
                'in_transit',
                'arrived_at_hub',
                'out_for_delivery',
                'delivered',
                'exception',
                'cancelled',
            ])->default('created')->change();

            $table->enum('current_status', [
                'CREATED',
                'CONFIRMED',
                'ASSIGNED',
                'PICKED_UP',
                'IN_TRANSIT',
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'CANCELLED',
            ])->default('CREATED')->change();
        });
    }

    /**
     * Migrate legacy status values to new ShipmentStatus enum values
     */
    private function migrateLegacyStatuses(): void
    {
        $mappings = [
            // Lowercase -> Uppercase canonical mapping
            'created' => 'BOOKED',
            'ready_for_pickup' => 'PICKUP_SCHEDULED',
            'in_transit' => 'LINEHAUL_DEPARTED',
            'arrived_at_hub' => 'AT_DESTINATION_HUB',
            'out_for_delivery' => 'OUT_FOR_DELIVERY',
            'delivered' => 'DELIVERED',
            'exception' => 'EXCEPTION',
            'cancelled' => 'CANCELLED',
            
            // Legacy uppercase values
            'CREATED' => 'BOOKED',
            'CONFIRMED' => 'BOOKED',
            'ASSIGNED' => 'PICKUP_SCHEDULED',
            'READY_FOR_PICKUP' => 'PICKUP_SCHEDULED',
            'READY_FOR_ASSIGNMENT' => 'BOOKED',
            'PENDING' => 'BOOKED',
            'PENDING_PICKUP' => 'PICKUP_SCHEDULED',
            'ASSIGNED_TO_WORKER' => 'PICKUP_SCHEDULED',
            'SCHEDULED' => 'PICKUP_SCHEDULED',
            'PICKED_UP' => 'PICKED_UP',
            'HANDED_OVER' => 'AT_ORIGIN_HUB',
            'ARRIVE' => 'AT_ORIGIN_HUB',
            'AT_HUB' => 'AT_ORIGIN_HUB',
            'ARRIVAL_ORIGIN' => 'AT_ORIGIN_HUB',
            'AT_ORIGIN' => 'AT_ORIGIN_HUB',
            'ORIGIN_SORT' => 'AT_ORIGIN_HUB',
            'SORT' => 'BAGGED',
            'LOAD' => 'BAGGED',
            'BAGGED' => 'BAGGED',
            'DEPART' => 'LINEHAUL_DEPARTED',
            'IN_TRANSIT' => 'LINEHAUL_DEPARTED',
            'LINEHAUL' => 'LINEHAUL_DEPARTED',
            'TRANSFER_TO_HUB' => 'LINEHAUL_DEPARTED',
            'TRANSFER_TO_DESTINATION' => 'LINEHAUL_DEPARTED',
            'IN_TRANSIT_TO_DESTINATION' => 'LINEHAUL_DEPARTED',
            'IN_TRANSIT_TO_HUB' => 'LINEHAUL_DEPARTED',
            'ARRIVE_DEST' => 'LINEHAUL_ARRIVED',
            'ARRIVED_DESTINATION' => 'LINEHAUL_ARRIVED',
            'ARRIVED_AT_DESTINATION' => 'LINEHAUL_ARRIVED',
            'LINEHAUL_ARRIVED' => 'LINEHAUL_ARRIVED',
            'DESTINATION_HUB' => 'AT_DESTINATION_HUB',
            'AT_DESTINATION' => 'AT_DESTINATION_HUB',
            'DESTINATION_SORT' => 'AT_DESTINATION_HUB',
            'OUT_FOR_DELIVERY' => 'OUT_FOR_DELIVERY',
            'DELIVERED' => 'DELIVERED',
            'RETURN_TO_SENDER' => 'RETURN_INITIATED',
            'RETURN_INITIATED' => 'RETURN_INITIATED',
            'RETURN_IN_TRANSIT' => 'RETURN_IN_TRANSIT',
            'RETURNED' => 'RETURNED',
            'CANCELLED' => 'CANCELLED',
            'EXCEPTION' => 'EXCEPTION',
            'DAMAGED' => 'EXCEPTION',
        ];

        foreach ($mappings as $old => $new) {
            DB::table('shipments')
                ->where('current_status', $old)
                ->update(['current_status' => $new]);
                
            // Also update lowercase status field
            DB::table('shipments')
                ->where('status', $old)
                ->update(['status' => strtolower($new)]);
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
