<?php

use App\Enums\ShipmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipments')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            // Relax enum columns to string to support canonical lifecycle values
            DB::statement("ALTER TABLE shipments MODIFY current_status VARCHAR(40) NOT NULL DEFAULT 'BOOKED'");
            DB::statement("ALTER TABLE shipments MODIFY status VARCHAR(40) NOT NULL DEFAULT 'booked'");
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'booked_at')) {
                $table->timestamp('booked_at')->nullable()->after('created_at');
            }
            if (! Schema::hasColumn('shipments', 'pickup_scheduled_at')) {
                $table->timestamp('pickup_scheduled_at')->nullable()->after('booked_at');
            }
            if (! Schema::hasColumn('shipments', 'picked_up_at')) {
                $table->timestamp('picked_up_at')->nullable()->after('pickup_scheduled_at');
            }
            if (! Schema::hasColumn('shipments', 'origin_hub_arrived_at')) {
                $table->timestamp('origin_hub_arrived_at')->nullable()->after('picked_up_at');
            }
            if (! Schema::hasColumn('shipments', 'bagged_at')) {
                $table->timestamp('bagged_at')->nullable()->after('origin_hub_arrived_at');
            }
            if (! Schema::hasColumn('shipments', 'linehaul_departed_at')) {
                $table->timestamp('linehaul_departed_at')->nullable()->after('bagged_at');
            }
            if (! Schema::hasColumn('shipments', 'linehaul_arrived_at')) {
                $table->timestamp('linehaul_arrived_at')->nullable()->after('linehaul_departed_at');
            }
            if (! Schema::hasColumn('shipments', 'destination_hub_arrived_at')) {
                $table->timestamp('destination_hub_arrived_at')->nullable()->after('linehaul_arrived_at');
            }
            if (! Schema::hasColumn('shipments', 'customs_hold_at')) {
                $table->timestamp('customs_hold_at')->nullable()->after('destination_hub_arrived_at');
            }
            if (! Schema::hasColumn('shipments', 'customs_cleared_at')) {
                $table->timestamp('customs_cleared_at')->nullable()->after('customs_hold_at');
            }
            if (! Schema::hasColumn('shipments', 'out_for_delivery_at')) {
                $table->timestamp('out_for_delivery_at')->nullable()->after('customs_cleared_at');
            }
            if (! Schema::hasColumn('shipments', 'return_initiated_at')) {
                $table->timestamp('return_initiated_at')->nullable()->after('out_for_delivery_at');
            }
            if (! Schema::hasColumn('shipments', 'return_in_transit_at')) {
                $table->timestamp('return_in_transit_at')->nullable()->after('return_initiated_at');
            }
            if (! Schema::hasColumn('shipments', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('return_in_transit_at');
            }
            if (! Schema::hasColumn('shipments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('returned_at');
            }
            if (! Schema::hasColumn('shipments', 'exception_occurred_at')) {
                $table->timestamp('exception_occurred_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('shipments', 'has_exception')) {
                $table->boolean('has_exception')->default(false)->after('exception_occurred_at');
            }
            if (! Schema::hasColumn('shipments', 'exception_type')) {
                $table->string('exception_type')->nullable()->after('has_exception');
            }
            if (! Schema::hasColumn('shipments', 'exception_severity')) {
                $table->string('exception_severity')->nullable()->after('exception_type');
            }
            if (! Schema::hasColumn('shipments', 'exception_notes')) {
                $table->text('exception_notes')->nullable()->after('exception_severity');
            }
            if (! Schema::hasColumn('shipments', 'return_reason')) {
                $table->string('return_reason')->nullable()->after('exception_notes');
            }
            if (! Schema::hasColumn('shipments', 'return_notes')) {
                $table->text('return_notes')->nullable()->after('return_reason');
            }
            if (! Schema::hasColumn('shipments', 'current_location_type')) {
                $table->string('current_location_type', 40)->nullable()->after('current_status');
            }
            if (! Schema::hasColumn('shipments', 'current_location_id')) {
                $table->unsignedBigInteger('current_location_id')->nullable()->after('current_location_type');
            }
            if (! Schema::hasColumn('shipments', 'last_scan_event_id')) {
                $foreign = $table->foreignId('last_scan_event_id')->nullable()->after('current_location_id');
                if (Schema::hasTable('scan_events')) {
                    $foreign->constrained('scan_events')->nullOnDelete();
                }
            }
        });

        $self = $this;
        Schema::table('shipments', function (Blueprint $table) use ($self, $driver) {
            if ($driver === 'sqlite') {
                return;
            }

            if (! $self->indexExists('shipments', 'shipments_current_status_index')) {
                $table->index('current_status', 'shipments_current_status_index');
            }

            foreach ([
                'booked_at',
                'pickup_scheduled_at',
                'picked_up_at',
                'origin_hub_arrived_at',
                'bagged_at',
                'linehaul_departed_at',
                'linehaul_arrived_at',
                'destination_hub_arrived_at',
                'customs_hold_at',
                'customs_cleared_at',
                'out_for_delivery_at',
                'return_initiated_at',
                'return_in_transit_at',
                'returned_at',
                'cancelled_at',
                'exception_occurred_at',
            ] as $column) {
                if (Schema::hasColumn('shipments', $column) && ! $self->indexExists('shipments', "shipments_{$column}_index")) {
                    $table->index($column, "shipments_{$column}_index");
                }
            }
            if (Schema::hasColumn('shipments', 'current_location_type') && ! $self->indexExists('shipments', 'shipments_current_location_type_index')) {
                $table->index('current_location_type', 'shipments_current_location_type_index');
            }
            if (Schema::hasColumn('shipments', 'current_location_id') && ! $self->indexExists('shipments', 'shipments_current_location_id_index')) {
                $table->index('current_location_id', 'shipments_current_location_id_index');
            }
        });

        // Normalise existing data into canonical lifecycle values
        $legacyMap = [
            'CREATED' => ShipmentStatus::BOOKED->value,
            'CONFIRMED' => ShipmentStatus::BOOKED->value,
            'READY_FOR_PICKUP' => ShipmentStatus::BOOKED->value,
            'PENDING' => ShipmentStatus::BOOKED->value,
            'ASSIGNED' => ShipmentStatus::PICKUP_SCHEDULED->value,
            'HANDED_OVER' => ShipmentStatus::AT_ORIGIN_HUB->value,
            'ARRIVE' => ShipmentStatus::AT_ORIGIN_HUB->value,
            'SORT' => ShipmentStatus::BAGGED->value,
            'LOAD' => ShipmentStatus::BAGGED->value,
            'DEPART' => ShipmentStatus::LINEHAUL_DEPARTED->value,
            'IN_TRANSIT' => ShipmentStatus::LINEHAUL_DEPARTED->value,
            'ARRIVE_DEST' => ShipmentStatus::LINEHAUL_ARRIVED->value,
            'OUT_FOR_DELIVERY' => ShipmentStatus::OUT_FOR_DELIVERY->value,
            'RETURN_TO_SENDER' => ShipmentStatus::RETURN_INITIATED->value,
            'DAMAGED' => ShipmentStatus::EXCEPTION->value,
            'DELIVERED' => ShipmentStatus::DELIVERED->value,
            'CANCELLED' => ShipmentStatus::CANCELLED->value,
            'EXCEPTION' => ShipmentStatus::EXCEPTION->value,
        ];

        foreach ($legacyMap as $from => $to) {
            DB::table('shipments')
                ->where('current_status', $from)
                ->update(['current_status' => $to]);
            DB::table('shipments')
                ->where('status', strtolower($from))
                ->update(['status' => strtolower($to)]);
        }

        // Seed missing temporal anchors where possible
        DB::table('shipments')
            ->whereNull('booked_at')
            ->update(['booked_at' => DB::raw('created_at')]);

        DB::table('shipments')
            ->whereNull('pickup_scheduled_at')
            ->where('current_status', ShipmentStatus::PICKUP_SCHEDULED->value)
            ->update(['pickup_scheduled_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('shipments')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        $indexedColumns = [
            'booked_at',
            'pickup_scheduled_at',
            'picked_up_at',
            'origin_hub_arrived_at',
            'bagged_at',
            'linehaul_departed_at',
            'linehaul_arrived_at',
            'destination_hub_arrived_at',
            'customs_hold_at',
            'customs_cleared_at',
            'out_for_delivery_at',
            'return_initiated_at',
            'return_in_transit_at',
            'returned_at',
            'cancelled_at',
            'exception_occurred_at',
        ];

        $columnsToDrop = array_merge($indexedColumns, [
            'current_location_type',
            'current_location_id',
            'last_scan_event_id',
            'has_exception',
            'exception_type',
            'exception_severity',
            'exception_notes',
            'return_reason',
            'return_notes',
        ]);

        $self = $this;

        Schema::table('shipments', function (Blueprint $table) use ($indexedColumns, $columnsToDrop, $driver, $self) {
            if (Schema::hasColumn('shipments', 'last_scan_event_id') && $driver !== 'sqlite') {
                $table->dropForeign(['last_scan_event_id']);
            }

            if ($driver !== 'sqlite') {
                if ($self->indexExists('shipments', 'shipments_current_status_index')) {
                    $table->dropIndex('shipments_current_status_index');
                }

                foreach ($indexedColumns as $column) {
                    $indexName = "shipments_{$column}_index";
                    if (Schema::hasColumn('shipments', $column) && $self->indexExists('shipments', $indexName)) {
                        $table->dropIndex($indexName);
                    }
                }

                foreach (['current_location_type', 'current_location_id'] as $column) {
                    $indexName = "shipments_{$column}_index";
                    if (Schema::hasColumn('shipments', $column) && $self->indexExists('shipments', $indexName)) {
                        $table->dropIndex($indexName);
                    }
                }
            }

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE shipments MODIFY current_status ENUM('CREATED','CONFIRMED','ASSIGNED','PICKED_UP','IN_TRANSIT','OUT_FOR_DELIVERY','DELIVERED','CANCELLED') NOT NULL DEFAULT 'CREATED'");
            DB::statement("ALTER TABLE shipments MODIFY status ENUM('created','ready_for_pickup','in_transit','arrived_at_hub','out_for_delivery','delivered','exception','cancelled') NOT NULL DEFAULT 'created'");
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(*) as aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index]
        );

        return ($result->aggregate ?? 0) > 0;
    }
};
