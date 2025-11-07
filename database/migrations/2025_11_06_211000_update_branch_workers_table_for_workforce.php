<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branch_workers')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table('branch_workers', function (Blueprint $table) {
            if (! Schema::hasColumn('branch_workers', 'designation')) {
                $table->string('designation', 120)->nullable()->after('role');
            }

            if (! Schema::hasColumn('branch_workers', 'employment_status')) {
                $table->string('employment_status', 40)->default('ACTIVE')->after('designation');
            }

            if (! Schema::hasColumn('branch_workers', 'contact_phone')) {
                $table->string('contact_phone', 30)->nullable()->after('employment_status');
            }

            if (! Schema::hasColumn('branch_workers', 'id_number')) {
                $table->string('id_number', 60)->nullable()->after('contact_phone');
            }

            if (! $this->indexExists('branch_workers', 'branch_workers_employment_status_index')) {
                $table->index('employment_status', 'branch_workers_employment_status_index');
            }
        });

        // Normalize role values to the new enumeration naming convention.
        $roleMappings = [
            'dispatcher' => 'DISPATCHER',
            'driver' => 'DRIVER',
            'supervisor' => 'OPS_SUPERVISOR',
            'warehouse_worker' => 'SORTATION_AGENT',
            'warehouse' => 'SORTATION_AGENT',
            'customer_service' => 'CUSTOMER_SUPPORT',
            'csr' => 'CUSTOMER_SUPPORT',
            'manager' => 'BRANCH_MANAGER',
            'branch_manager' => 'BRANCH_MANAGER',
            'operations' => 'OPS_AGENT',
            'ops_agent' => 'OPS_AGENT',
            'worker' => 'OPS_AGENT',
        ];

        foreach ($roleMappings as $legacy => $modern) {
            DB::table('branch_workers')->where('role', $legacy)->update(['role' => $modern]);
            DB::table('branch_workers')->where('role', strtoupper($legacy))->update(['role' => $modern]);
        }

        // Default employment status based on legacy status flag.
        DB::table('branch_workers')->where('status', 0)->update(['employment_status' => 'INACTIVE']);

        // Populate contact phone from related user accounts when available.
        if ($driver !== 'sqlite' && Schema::hasTable('users')) {
            DB::statement(<<<SQL
                UPDATE branch_workers bw
                JOIN users u ON u.id = bw.user_id
                SET bw.contact_phone = COALESCE(bw.contact_phone, u.mobile, u.phone, u.phone_e164)
                WHERE bw.contact_phone IS NULL
            SQL);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('branch_workers')) {
            return;
        }

        // Revert role values to legacy naming where possible.
        $roleRollback = [
            'DISPATCHER' => 'dispatcher',
            'DRIVER' => 'driver',
            'OPS_SUPERVISOR' => 'supervisor',
            'SORTATION_AGENT' => 'warehouse_worker',
            'CUSTOMER_SUPPORT' => 'customer_service',
            'BRANCH_MANAGER' => 'branch_manager',
            'OPS_AGENT' => 'worker',
        ];

        foreach ($roleRollback as $modern => $legacy) {
            DB::table('branch_workers')->where('role', $modern)->update(['role' => $legacy]);
        }

        Schema::table('branch_workers', function (Blueprint $table) {
            if ($this->indexExists('branch_workers', 'branch_workers_employment_status_index')) {
                $table->dropIndex('branch_workers_employment_status_index');
            }
        });

        foreach (['id_number', 'contact_phone', 'employment_status', 'designation'] as $column) {
            if (Schema::hasColumn('branch_workers', $column)) {
                Schema::table('branch_workers', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
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
