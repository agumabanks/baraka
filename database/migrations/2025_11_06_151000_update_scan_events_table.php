<?php

use App\Enums\ScanType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scan_events')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        $enumValues = implode("','", array_map(fn (ScanType $type) => $type->value, ScanType::cases()));
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE scan_events MODIFY type ENUM('{$enumValues}') NOT NULL");
        }

        Schema::table('scan_events', function (Blueprint $table) {
            if (! Schema::hasColumn('scan_events', 'shipment_id')) {
                $table->foreignId('shipment_id')->nullable()->after('sscc')->constrained('shipments')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('scan_events', 'bag_id')) {
                $table->foreignId('bag_id')->nullable()->after('shipment_id')->constrained('bags')->nullOnDelete();
            }
            if (! Schema::hasColumn('scan_events', 'route_id')) {
                $table->foreignId('route_id')->nullable()->after('bag_id')->constrained('routes')->nullOnDelete();
            }
            if (! Schema::hasColumn('scan_events', 'stop_id')) {
                $table->foreignId('stop_id')->nullable()->after('route_id')->constrained('stops')->nullOnDelete();
            }
            if (! Schema::hasColumn('scan_events', 'status_after')) {
                $table->string('status_after', 40)->nullable()->after('type');
            }
            if (! Schema::hasColumn('scan_events', 'location_type')) {
                $table->string('location_type', 40)->nullable()->after('branch_id');
            }
            if (! Schema::hasColumn('scan_events', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('location_type');
            }
            if (! Schema::hasColumn('scan_events', 'payload')) {
                $table->json('payload')->nullable()->after('geojson');
            }
        });

        $self = $this;
        Schema::table('scan_events', function (Blueprint $table) use ($driver, $self) {
            if ($driver === 'sqlite') {
                return;
            }

            foreach ([
                'shipment_id',
                'bag_id',
                'route_id',
                'stop_id',
                'status_after',
                'location_type',
                'location_id',
            ] as $column) {
                $indexName = "scan_events_{$column}_index";
                if (Schema::hasColumn('scan_events', $column) && ! $self->indexExists('scan_events', $indexName)) {
                    $table->index($column, $indexName);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('scan_events')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table('scan_events', function (Blueprint $table) use ($driver) {
            foreach (['shipment_id', 'bag_id', 'route_id', 'stop_id'] as $foreign) {
                if (Schema::hasColumn('scan_events', $foreign)) {
                    if ($driver !== 'sqlite') {
                        $table->dropForeign([$foreign]);
                    }
                    $table->dropColumn($foreign);
                }
            }

            foreach (['status_after', 'location_type', 'location_id', 'payload'] as $column) {
                if (Schema::hasColumn('scan_events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE scan_events MODIFY type ENUM('ARRIVE','SORT','LOAD','DEPART','IN_TRANSIT','CUSTOMS_HOLD','CUSTOMS_CLEARED','ARRIVE_DEST','OUT_FOR_DELIVERY','DELIVERED','RETURN_TO_SENDER','DAMAGED') NOT NULL");
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
