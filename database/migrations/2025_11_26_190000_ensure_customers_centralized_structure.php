<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ensures customers table has proper structure for centralized client management:
     * - primary_branch_id: The main branch this customer is associated with
     * - Customers are system-wide but branches can only see their own
     * - Admin can see all customers with branch associations
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Ensure primary_branch_id is properly indexed
            if (!$this->hasIndex('customers', 'customers_primary_branch_id_index')) {
                $table->index('primary_branch_id');
            }

            // Add created_by_branch_id to track which branch created the customer
            if (!Schema::hasColumn('customers', 'created_by_branch_id')) {
                $table->unsignedBigInteger('created_by_branch_id')->nullable()->after('primary_branch_id');
                $table->foreign('created_by_branch_id')->references('id')->on('branches')->onDelete('set null');
            }

            // Add created_by_user_id to track who created the customer
            if (!Schema::hasColumn('customers', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('created_by_branch_id');
                $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            }

            // Indexes for performance
            if (!$this->hasIndex('customers', 'customers_created_by_branch_id_index')) {
                $table->index('created_by_branch_id');
            }
            if (!$this->hasIndex('customers', 'customers_status_index')) {
                $table->index('status');
            }
            if (!$this->hasIndex('customers', 'customers_customer_type_index')) {
                $table->index('customer_type');
            }
        });

        // Update CRM activities to have branch context
        Schema::table('crm_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_activities', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('customer_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->index('branch_id');
            }
        });

        // Update CRM reminders to have branch context
        Schema::table('crm_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_reminders', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('customer_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->index('branch_id');
            }
        });

        // Update client addresses to have branch context
        Schema::table('client_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('client_addresses', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('customer_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->index('branch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_addresses', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('crm_reminders', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('crm_activities', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['customer_type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_by_branch_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['created_by_branch_id']);
            $table->dropColumn(['created_by_branch_id', 'created_by_user_id']);
        });
    }

    /**
     * Check if index exists
     */
    private function hasIndex(string $table, string $index): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return count($indexes) > 0;
    }
};
