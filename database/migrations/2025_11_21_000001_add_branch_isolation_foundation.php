<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds branch_id to core tables for multi-tenant isolation.
     * Creates audit tables for branch access tracking.
     */
    public function up(): void
    {
        // Add branch_id to tables that don't have it yet
        $tablesToAddBranchId = [
            'users', // For branch assignment beyond primary_branch_id
            'invoices',
            'payments',
            'support_tickets',
            'communication_logs',
        ];

        foreach ($tablesToAddBranchId as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                    $table->index('branch_id');
                    $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                });
            }
        }

        // Create branch access audit log
        Schema::create('branch_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('action'); // 'switched', 'accessed', 'bypassed_scope'
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable(); // Additional context data
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['branch_id', 'occurred_at']);
            $table->index('action');
            
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        // Create branch isolation settings table
        Schema::create('branch_isolation_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->unique();
            $table->boolean('strict_isolation')->default(true);
            $table->boolean('allow_cross_branch_viewing')->default(false);
            $table->json('isolation_exceptions')->nullable(); // Models/features exempt from isolation
            $table->json('cross_branch_permissions')->nullable(); // Specific cross-branch permissions
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        // Create regional branch groups for Regional Managers
        Schema::create('regional_branch_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('region_manager_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('region_manager_id')->references('id')->on('users')->nullOnDelete();
        });

        // Pivot table for branches in regional groups
        Schema::create('regional_branch_group_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regional_group_id');
            $table->unsignedBigInteger('branch_id');
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['regional_group_id', 'branch_id'], 'rbgb_group_branch_unique');
            $table->foreign('regional_group_id')->references('id')->on('regional_branch_groups')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop regional tables
        Schema::dropIfExists('regional_branch_group_branches');
        Schema::dropIfExists('regional_branch_groups');
        Schema::dropIfExists('branch_isolation_settings');
        Schema::dropIfExists('branch_access_logs');

        // Remove branch_id from tables
        $tablesToRemoveBranchId = [
            'users',
            'invoices',
            'payments',
            'support_tickets',
            'communication_logs',
        ];

        foreach ($tablesToRemoveBranchId as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropIndex(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
