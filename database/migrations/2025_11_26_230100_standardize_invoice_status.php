<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Standardizes invoice status to use string enums instead of numeric values.
     * Aligns with InvoiceStatus enum for DHL-grade consistency.
     */
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: Just ensure the status column exists and add index
            if (!Schema::hasColumn('invoices', 'status')) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->string('status', 20)->default('DRAFT');
                });
            }
            return;
        }

        // Step 1: Add temporary column for migration
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status_new', 20)->nullable()->after('status');
        });

        // Step 2: Migrate numeric statuses to string enums
        $this->migrateStatusValues();

        // Step 3: Drop old status column and rename new one
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });

        // Step 4: Add proper enum constraint
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', [
                'DRAFT',
                'PENDING',
                'SENT',
                'PAID',
                'OVERDUE',
                'CANCELLED',
                'REFUNDED',
            ])->default('DRAFT')->change();
        });

        // Step 5: Add index for performance
        Schema::table('invoices', function (Blueprint $table) {
            if (!$this->indexExists('invoices', 'invoices_status_index')) {
                $table->index('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        // Add temporary numeric column
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('status_numeric')->nullable()->after('status');
        });

        // Migrate back to numeric values
        DB::table('invoices')->where('status', 'DRAFT')->update(['status_numeric' => 1]);
        DB::table('invoices')->where('status', 'PENDING')->update(['status_numeric' => 2]);
        DB::table('invoices')->where('status', 'SENT')->update(['status_numeric' => 2]);
        DB::table('invoices')->where('status', 'PAID')->update(['status_numeric' => 3]);
        DB::table('invoices')->where('status', 'OVERDUE')->update(['status_numeric' => 4]);
        DB::table('invoices')->where('status', 'CANCELLED')->update(['status_numeric' => 5]);
        DB::table('invoices')->where('status', 'REFUNDED')->update(['status_numeric' => 5]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('status_numeric', 'status');
        });
    }

    /**
     * Migrate status values from numeric to string enum
     */
    private function migrateStatusValues(): void
    {
        $mappings = [
            1 => 'DRAFT',
            2 => 'PENDING',
            3 => 'PAID',
            4 => 'OVERDUE',
            5 => 'CANCELLED',
            '1' => 'DRAFT',
            '2' => 'PENDING',
            '3' => 'PAID',
            '4' => 'OVERDUE',
            '5' => 'CANCELLED',
            'PENDING' => 'PENDING',
            'PAID' => 'PAID',
            'OVERDUE' => 'OVERDUE',
            'CANCELLED' => 'CANCELLED',
            'DRAFT' => 'DRAFT',
        ];

        foreach ($mappings as $old => $new) {
            DB::table('invoices')
                ->where('status', $old)
                ->update(['status_new' => $new]);
        }

        // Set default for any NULL or unmapped values
        DB::table('invoices')
            ->whereNull('status_new')
            ->update(['status_new' => 'DRAFT']);
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?", [$table, $index]);
            return !empty($indexes);
        }
        
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};
