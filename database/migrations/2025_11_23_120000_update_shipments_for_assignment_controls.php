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
            if (! Schema::hasColumn('shipments', 'held_at')) {
                $table->timestamp('held_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('shipments', 'held_by')) {
                $column = $table->foreignId('held_by')->nullable()->after('held_at');
                if (Schema::hasTable('users')) {
                    $column->constrained('users')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('shipments', 'hold_reason')) {
                $table->string('hold_reason')->nullable()->after('held_by');
            }
            if (! Schema::hasColumn('shipments', 'rerouted_from_branch_id')) {
                $column = $table->foreignId('rerouted_from_branch_id')->nullable()->after('dest_branch_id');
                if (Schema::hasTable('branches')) {
                    $column->constrained('branches')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('shipments', 'rerouted_at')) {
                $table->timestamp('rerouted_at')->nullable()->after('rerouted_from_branch_id');
            }
            if (! Schema::hasColumn('shipments', 'rerouted_by')) {
                $column = $table->foreignId('rerouted_by')->nullable()->after('rerouted_at');
                if (Schema::hasTable('users')) {
                    $column->constrained('users')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('shipments', 'barcode')) {
                $table->string('barcode')->nullable()->unique()->after('tracking_number');
            }
            if (! Schema::hasColumn('shipments', 'qr_code')) {
                $table->string('qr_code')->nullable()->after('barcode');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            foreach (['held_at', 'held_by', 'hold_reason', 'rerouted_from_branch_id', 'rerouted_at', 'rerouted_by'] as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
