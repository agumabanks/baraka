<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE branches MODIFY COLUMN type VARCHAR(40) NOT NULL");
        }

        // Ensure new structural columns exist.
        if (! Schema::hasColumn('branches', 'country')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('country', 120)->nullable()->after('address');
            });
        }

        if (! Schema::hasColumn('branches', 'city')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('city', 120)->nullable()->after('country');
            });
        }

        if (! Schema::hasColumn('branches', 'time_zone')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('time_zone', 64)->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('branches', 'capacity_parcels_per_day')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->unsignedInteger('capacity_parcels_per_day')->nullable()->after('capabilities');
            });
        }

        if (! Schema::hasColumn('branches', 'geo_lat')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->decimal('geo_lat', 10, 8)->nullable()->after('longitude');
            });
        }

        if (! Schema::hasColumn('branches', 'geo_lng')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->decimal('geo_lng', 11, 8)->nullable()->after('geo_lat');
            });
        }

        // Backfill geo coordinates from legacy columns where applicable.
        if (Schema::hasColumn('branches', 'latitude') && Schema::hasColumn('branches', 'geo_lat')) {
            DB::statement('UPDATE branches SET geo_lat = latitude WHERE geo_lat IS NULL AND latitude IS NOT NULL');
        }

        if (Schema::hasColumn('branches', 'longitude') && Schema::hasColumn('branches', 'geo_lng')) {
            DB::statement('UPDATE branches SET geo_lng = longitude WHERE geo_lng IS NULL AND longitude IS NOT NULL');
        }

        // Normalize existing type values to the new vocabulary.
        DB::table('branches')->where('type', 'REGIONAL')->update(['type' => 'REGIONAL_BRANCH']);
        DB::table('branches')->where('type', 'LOCAL')->update(['type' => 'DESTINATION_BRANCH']);

        $this->addIndexIfMissing('branches', 'country', 'branches_country_index');
        $this->addIndexIfMissing('branches', 'city', 'branches_city_index');
        $this->addIndexIfMissing('branches', 'time_zone', 'branches_time_zone_index');
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        $this->dropIndexIfExists('branches', 'branches_country_index');
        $this->dropIndexIfExists('branches', 'branches_city_index');
        $this->dropIndexIfExists('branches', 'branches_time_zone_index');

        // Revert type values to legacy options before changing column definition.
        DB::table('branches')->where('type', 'REGIONAL_BRANCH')->update(['type' => 'REGIONAL']);
        DB::table('branches')->where('type', 'DESTINATION_BRANCH')->update(['type' => 'LOCAL']);
        DB::table('branches')->where('type', 'AGENT_POINT')->update(['type' => 'LOCAL']);
        DB::table('branches')->where('type', 'MICRO_DEPOT')->update(['type' => 'LOCAL']);
        DB::table('branches')->where('type', 'FULFILLMENT_CENTER')->update(['type' => 'LOCAL']);

        foreach (['geo_lng', 'geo_lat', 'capacity_parcels_per_day', 'time_zone', 'city', 'country'] as $column) {
            if (Schema::hasColumn('branches', $column)) {
                Schema::table('branches', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE branches MODIFY COLUMN type ENUM('HUB','REGIONAL','LOCAL') NOT NULL DEFAULT 'LOCAL'");
        }
    }

    protected function addIndexIfMissing(string $tableName, string $column, string $index): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if ($this->indexExists($tableName, $index)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $blueprint) use ($column, $index) {
            $blueprint->index($column, $index);
        });
    }

    protected function dropIndexIfExists(string $tableName, string $index): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! $this->indexExists($tableName, $index)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropIndex($index);
        });
    }

    protected function indexExists(string $tableName, string $index): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(*) as aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $tableName, $index]
        );

        return ($result->aggregate ?? 0) > 0;
    }
};
