<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Exception tracking fields
            if (!Schema::hasColumn('shipments', 'exception_category')) {
                $table->string('exception_category', 50)->nullable()->after('has_exception');
            }
            if (!Schema::hasColumn('shipments', 'exception_severity')) {
                $table->string('exception_severity', 20)->nullable()->after('exception_category');
            }
            if (!Schema::hasColumn('shipments', 'exception_description')) {
                $table->text('exception_description')->nullable()->after('exception_severity');
            }
            if (!Schema::hasColumn('shipments', 'exception_root_cause')) {
                $table->string('exception_root_cause', 500)->nullable()->after('exception_description');
            }
            if (!Schema::hasColumn('shipments', 'exception_flagged_at')) {
                $table->timestamp('exception_flagged_at')->nullable()->after('exception_root_cause');
            }
            if (!Schema::hasColumn('shipments', 'exception_flagged_by')) {
                $table->unsignedBigInteger('exception_flagged_by')->nullable()->after('exception_flagged_at');
            }
            if (!Schema::hasColumn('shipments', 'exception_resolved_at')) {
                $table->timestamp('exception_resolved_at')->nullable()->after('exception_flagged_by');
            }
            if (!Schema::hasColumn('shipments', 'exception_resolved_by')) {
                $table->unsignedBigInteger('exception_resolved_by')->nullable()->after('exception_resolved_at');
            }
            if (!Schema::hasColumn('shipments', 'exception_resolution')) {
                $table->text('exception_resolution')->nullable()->after('exception_resolved_by');
            }
            if (!Schema::hasColumn('shipments', 'exception_resolution_type')) {
                $table->string('exception_resolution_type', 30)->nullable()->after('exception_resolution');
            }
            if (!Schema::hasColumn('shipments', 'exception_action_taken')) {
                $table->text('exception_action_taken')->nullable()->after('exception_resolution_type');
            }
            if (!Schema::hasColumn('shipments', 'exception_escalated_at')) {
                $table->timestamp('exception_escalated_at')->nullable()->after('exception_action_taken');
            }
            if (!Schema::hasColumn('shipments', 'exception_escalated_by')) {
                $table->unsignedBigInteger('exception_escalated_by')->nullable()->after('exception_escalated_at');
            }
            if (!Schema::hasColumn('shipments', 'exception_escalation_reason')) {
                $table->text('exception_escalation_reason')->nullable()->after('exception_escalated_by');
            }
        });

        // Add indexes for exception queries (safe to fail if already exists)
        try {
            Schema::table('shipments', function (Blueprint $table) {
                $table->index('exception_category', 'shipments_exception_category_index');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
        
        try {
            Schema::table('shipments', function (Blueprint $table) {
                $table->index('exception_severity', 'shipments_exception_severity_index');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
        
        try {
            Schema::table('shipments', function (Blueprint $table) {
                $table->index('exception_resolved_at', 'shipments_exception_resolved_at_index');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $columns = [
                'exception_category',
                'exception_severity',
                'exception_description',
                'exception_root_cause',
                'exception_flagged_at',
                'exception_flagged_by',
                'exception_resolved_at',
                'exception_resolved_by',
                'exception_resolution',
                'exception_resolution_type',
                'exception_action_taken',
                'exception_escalated_at',
                'exception_escalated_by',
                'exception_escalation_reason',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
