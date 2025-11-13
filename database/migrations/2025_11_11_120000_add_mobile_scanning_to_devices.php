<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'device_id')) {
                $table->string('device_id')->nullable()->after('device_uuid')->comment('Unique device identifier for mobile scanning');
            }

            if (! Schema::hasColumn('devices', 'device_name')) {
                $table->string('device_name')->nullable()->after('device_id')->comment('Human readable device name');
            }

            if (! Schema::hasColumn('devices', 'device_token')) {
                $table->string('device_token')->nullable()->after('device_name')->comment('Secure token for device authentication');
            }

            if (! Schema::hasColumn('devices', 'app_version')) {
                $table->string('app_version', 20)->nullable()->after('device_token')->comment('Mobile app version');
            }

            if (! Schema::hasColumn('devices', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('app_version')->comment('Firebase Cloud Messaging token');
            }

            if (! Schema::hasColumn('devices', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('fcm_token')->comment('Whether device is active for scanning');
            }
        });

        $self = $this;

        Schema::table('devices', function (Blueprint $table) use ($self) {
            if (Schema::hasColumn('devices', 'device_id') && ! $self->indexExists('devices', 'unique_device_id_for_mobile_scanning')) {
                $table->unique('device_id', 'unique_device_id_for_mobile_scanning');
            }

            if (Schema::hasColumn('devices', 'device_id') && ! $self->indexExists('devices', 'devices_device_id_index')) {
                $table->index('device_id');
            }

            if (Schema::hasColumn('devices', 'device_token') && ! $self->indexExists('devices', 'devices_device_token_index')) {
                $table->index('device_token');
            }

            if (Schema::hasColumn('devices', 'is_active') && Schema::hasColumn('devices', 'platform') && ! $self->indexExists('devices', 'devices_is_active_platform_index')) {
                $table->index(['is_active', 'platform'], 'devices_is_active_platform_index');
            }

            if (Schema::hasColumn('devices', 'last_seen_at') && ! $self->indexExists('devices', 'devices_last_seen_at_index')) {
                $table->index('last_seen_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        $self = $this;

        Schema::table('devices', function (Blueprint $table) use ($self) {
            if ($self->indexExists('devices', 'unique_device_id_for_mobile_scanning')) {
                $table->dropUnique('unique_device_id_for_mobile_scanning');
            }

            if ($self->indexExists('devices', 'devices_device_id_index')) {
                $table->dropIndex('devices_device_id_index');
            }

            if ($self->indexExists('devices', 'devices_device_token_index')) {
                $table->dropIndex('devices_device_token_index');
            }

            if ($self->indexExists('devices', 'devices_is_active_platform_index')) {
                $table->dropIndex('devices_is_active_platform_index');
            }

            if ($self->indexExists('devices', 'devices_last_seen_at_index')) {
                $table->dropIndex('devices_last_seen_at_index');
            }

            foreach (['device_id', 'device_name', 'device_token', 'app_version', 'fcm_token', 'is_active'] as $column) {
                if (Schema::hasColumn('devices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return false;
        }

        $database = $connection->getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(*) as aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index]
        );

        return ($result->aggregate ?? 0) > 0;
    }
};