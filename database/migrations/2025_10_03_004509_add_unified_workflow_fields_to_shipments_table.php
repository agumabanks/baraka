<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
<<<<<<< ours
            // Critical: Tracking number for shipment identification
            $table->string('tracking_number', 50)->unique()->after('id')->nullable();
            
            // Priority
            $table->integer('priority')->default(1)->after('service_level')->comment('1=standard, 2=priority, 3=express');
            
            // Worker assignment
            $table->foreignId('assigned_worker_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_worker_id');
            
            // Delivery worker
            $table->foreignId('delivered_by')->nullable()->after('assigned_at')->constrained('users')->nullOnDelete();
            
            // Hub and transfer management timestamps
            $table->timestamp('hub_processed_at')->nullable()->after('delivered_by');
            $table->timestamp('transferred_at')->nullable()->after('hub_processed_at');
            $table->timestamp('picked_up_at')->nullable()->after('transferred_at');
            $table->timestamp('processed_at')->nullable()->after('picked_up_at');
            $table->timestamp('delivered_at')->nullable()->after('processed_at');
            
            // Exception management
            $table->boolean('has_exception')->default(false)->after('current_status');
            $table->string('exception_type')->nullable()->after('has_exception');
            $table->enum('exception_severity', ['low', 'medium', 'high'])->nullable()->after('exception_type');
            $table->text('exception_notes')->nullable()->after('exception_severity');
            $table->timestamp('exception_occurred_at')->nullable()->after('exception_notes');
            
            // Return management
            $table->timestamp('returned_at')->nullable()->after('delivered_at');
            $table->string('return_reason')->nullable()->after('returned_at');
            $table->text('return_notes')->nullable()->after('return_reason');
            
            // Indexes for performance
            $table->index('tracking_number');
            $table->index('assigned_worker_id');
=======
            if (! Schema::hasColumn('shipments', 'transfer_hub_id')) {
                $table->foreignId('transfer_hub_id')->nullable()->after('dest_branch_id')->constrained('branches')->nullOnDelete();
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

            $table->index('transfer_hub_id');
>>>>>>> theirs
            $table->index('delivered_by');
            $table->index('has_exception');
            $table->index('priority');
            $table->index('hub_processed_at');
            $table->index('exception_occurred_at');
            $table->index('assigned_at');
            $table->index('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
<<<<<<< ours
            // Drop indexes first
            $table->dropIndex(['tracking_number']);
            $table->dropIndex(['assigned_worker_id']);
            $table->dropIndex(['delivered_by']);
            $table->dropIndex(['has_exception']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['hub_processed_at']);
            $table->dropIndex(['exception_occurred_at']);
            $table->dropIndex(['assigned_at']);
            $table->dropIndex(['delivered_at']);
            
            // Drop foreign keys
            $table->dropForeign(['assigned_worker_id']);
            $table->dropForeign(['delivered_by']);
            
            // Drop columns
            $table->dropColumn([
                'tracking_number',
                'priority',
                'assigned_worker_id',
                'assigned_at',
                'delivered_by',
=======
            if (Schema::hasColumn('shipments', 'transfer_hub_id')) {
                $table->dropForeign(['transfer_hub_id']);
                $table->dropColumn('transfer_hub_id');
            }
            if (Schema::hasColumn('shipments', 'delivered_by')) {
                $table->dropForeign(['delivered_by']);
                $table->dropColumn('delivered_by');
            }

            foreach ([
>>>>>>> theirs
                'hub_processed_at',
                'transferred_at',
                'picked_up_at',
                'processed_at',
<<<<<<< ours
                'delivered_at',
=======
>>>>>>> theirs
                'has_exception',
                'exception_type',
                'exception_severity',
                'exception_notes',
                'exception_occurred_at',
                'returned_at',
                'return_reason',
                'return_notes',
<<<<<<< ours
            ]);
=======
                'priority',
            ] as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
>>>>>>> theirs
        });
    }
};
