<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Hub and transfer management
            $table->foreignId('transfer_hub_id')->nullable()->after('dest_branch_id')->constrained('unified_branches')->nullOnDelete();
            $table->timestamp('hub_processed_at')->nullable()->after('assigned_at');
            $table->timestamp('transferred_at')->nullable()->after('hub_processed_at');
            $table->timestamp('picked_up_at')->nullable()->after('transferred_at');
            $table->timestamp('processed_at')->nullable()->after('picked_up_at');
            
            // Delivery worker
            $table->foreignId('delivered_by')->nullable()->after('assigned_worker_id')->constrained('users')->nullOnDelete();
            
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
            
            // Priority
            $table->integer('priority')->default(1)->after('service_level')->comment('1=standard, 2=priority, 3=express');
            
            // Indexes for performance
            $table->index('transfer_hub_id');
            $table->index('delivered_by');
            $table->index('has_exception');
            $table->index('priority');
            $table->index('hub_processed_at');
            $table->index('exception_occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['transfer_hub_id']);
            $table->dropIndex(['delivered_by']);
            $table->dropIndex(['has_exception']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['hub_processed_at']);
            $table->dropIndex(['exception_occurred_at']);
            
            // Drop foreign keys
            $table->dropForeign(['transfer_hub_id']);
            $table->dropForeign(['delivered_by']);
            
            // Drop columns
            $table->dropColumn([
                'transfer_hub_id',
                'hub_processed_at',
                'transferred_at',
                'picked_up_at',
                'processed_at',
                'delivered_by',
                'has_exception',
                'exception_type',
                'exception_severity',
                'exception_notes',
                'exception_occurred_at',
                'returned_at',
                'return_reason',
                'return_notes',
                'priority',
            ]);
        });
    }
};
