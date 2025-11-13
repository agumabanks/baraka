<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'primary_branch_id')) {
                $table->unsignedBigInteger('primary_branch_id')->nullable()->after('hub_id');
                $table->foreign('primary_branch_id')->references('id')->on('branches')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('users', 'primary_branch_id')) {
            $this->backfillPrimaryBranchFromManagers();
            $this->backfillPrimaryBranchFromWorkers();
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'primary_branch_id')) {
                $table->dropForeign(['primary_branch_id']);
                $table->dropColumn('primary_branch_id');
            }
        });
    }

    private function backfillPrimaryBranchFromManagers(): void
    {
        if (! Schema::hasTable('branch_managers')) {
            return;
        }

        $managers = DB::table('branch_managers')
            ->select('user_id', 'branch_id')
            ->whereNotNull('user_id')
            ->get();

        foreach ($managers as $manager) {
            DB::table('users')
                ->where('id', $manager->user_id)
                ->whereNull('primary_branch_id')
                ->update(['primary_branch_id' => $manager->branch_id]);
        }
    }

    private function backfillPrimaryBranchFromWorkers(): void
    {
        if (! Schema::hasTable('branch_workers')) {
            return;
        }

        $workers = DB::table('branch_workers')
            ->select('user_id', 'branch_id')
            ->whereNotNull('user_id')
            ->get();

        foreach ($workers as $worker) {
            DB::table('users')
                ->where('id', $worker->user_id)
                ->whereNull('primary_branch_id')
                ->update(['primary_branch_id' => $worker->branch_id]);
        }
    }
};
