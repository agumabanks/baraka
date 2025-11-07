<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'transfer_hub_id')) {
                $transferHubColumn = $table->foreignId('transfer_hub_id')->nullable()->after('dest_branch_id');
                if (Schema::hasTable('unified_branches')) {
                    $transferHubColumn->constrained('unified_branches')->nullOnDelete();
                } elseif (Schema::hasTable('branches')) {
                    $transferHubColumn->constrained('branches')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('shipments', 'hub_processed_at')) {
                $table->timestamp('hub_processed_at')->nullable()->after('assigned_at');
            }
            if (! Schema::hasColumn('shipments', 'transferred_at')) {
                $table->timestamp('transferred_at')->nullable()->after('hub_processed_at');
            }
            if (! Schema::hasColumn('shipments', 'picked_up_at')) {
                $table->timestamp('picked_up_at')->nullable()->after('transferred_at');
            }
            if (! Schema::hasColumn('shipments', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('picked_up_at');
            }
            if (! Schema::hasColumn('shipments', 'delivered_by')) {
                $table->foreignId('delivered_by')->nullable()->after('assigned_worker_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('shipments', 'has_exception')) {
                $table->boolean('has_exception')->default(false)->after('status');
            }
            if (! Schema::hasColumn('shipments', 'exception_type')) {
                $table->string('exception_type')->nullable()->after('has_exception');
            }
            if (! Schema::hasColumn('shipments', 'exception_severity')) {
                $table->enum('exception_severity', ['low', 'medium', 'high'])->nullable()->after('exception_type');
            }
            if (! Schema::hasColumn('shipments', 'exception_notes')) {
                $table->text('exception_notes')->nullable()->after('exception_severity');
            }
            if (! Schema::hasColumn('shipments', 'exception_occurred_at')) {
                $table->timestamp('exception_occurred_at')->nullable()->after('exception_notes');
            }
            if (! Schema::hasColumn('shipments', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('delivered_at');
            }
            if (! Schema::hasColumn('shipments', 'return_reason')) {
                $table->string('return_reason')->nullable()->after('returned_at');
            }
            if (! Schema::hasColumn('shipments', 'return_notes')) {
                $table->text('return_notes')->nullable()->after('return_reason');
            }
            if (! Schema::hasColumn('shipments', 'priority')) {
                $table->integer('priority')->default(1)->after('service_level');
            }
            if (! Schema::hasColumn('shipments', 'tracking_number')) {
                $table->string('tracking_number', 50)->unique()->after('id')->nullable();
            }
            if (! Schema::hasColumn('shipments', 'assigned_worker_id')) {
                $table->foreignId('assigned_worker_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('shipments', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_worker_id');
            }

            if (! Schema::hasIndex('shipments', 'shipments_transfer_hub_id_index')) {
                $table->index('transfer_hub_id');
            }
            if (! Schema::hasIndex('shipments', 'shipments_delivered_by_index')) {
                $table->index('delivered_by');
            }
            if (! Schema::hasIndex('shipments', 'shipments_has_exception_index')) {
                $table->index('has_exception');
            }
            if (! Schema::hasIndex('shipments', 'shipments_priority_index')) {
                $table->index('priority');
            }
            if (! Schema::hasIndex('shipments', 'shipments_hub_processed_at_index')) {
                $table->index('hub_processed_at');
            }
            if (! Schema::hasIndex('shipments', 'shipments_exception_occurred_at_index')) {
                $table->index('exception_occurred_at');
            }
            if (! Schema::hasIndex('shipments', 'shipments_assigned_at_index')) {
                $table->index('assigned_at');
            }
            if (! Schema::hasIndex('shipments', 'shipments_delivered_at_index')) {
                $table->index('delivered_at');
            }
            if (! Schema::hasIndex('shipments', 'shipments_tracking_number_index') && ! Schema::hasIndex('shipments', 'shipments_tracking_number_unique', 'unique')) {
                $table->index('tracking_number');
            }
            if (! Schema::hasIndex('shipments', 'shipments_assigned_worker_id_index')) {
                $table->index('assigned_worker_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'transfer_hub_id')) {
                $table->dropForeign(['transfer_hub_id']);
                $table->dropColumn('transfer_hub_id');
            }
            if (Schema::hasColumn('shipments', 'delivered_by')) {
                $table->dropForeign(['delivered_by']);
                $table->dropColumn('delivered_by');
            }

            foreach ([
                'hub_processed_at',
                'transferred_at',
                'picked_up_at',
                'processed_at',
                'has_exception',
                'exception_type',
                'exception_severity',
                'exception_notes',
                'exception_occurred_at',
                'returned_at',
                'return_reason',
                'return_notes',
                'priority',
                'tracking_number',
                'assigned_worker_id',
                'assigned_at',
            ] as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
