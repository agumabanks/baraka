<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Branch expenses table
        if (!Schema::hasTable('branch_expenses')) {
            Schema::create('branch_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('category', 50);
                $table->string('description', 255);
                $table->decimal('amount', 15, 2);
                $table->date('expense_date');
                $table->string('receipt_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamps();
                
                $table->index(['branch_id', 'expense_date']);
                $table->index(['branch_id', 'category']);
            });
        }

        // COD remittances table
        if (!Schema::hasTable('cod_remittances')) {
            Schema::create('cod_remittances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->string('currency', 3)->default('UGX');
                $table->string('remittance_method', 50);
                $table->string('reference_number', 100)->nullable();
                $table->foreignId('remitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('remitted_at');
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamps();
                
                $table->index(['branch_id', 'remitted_at']);
            });
        }

        // Cycle counts table
        if (!Schema::hasTable('cycle_counts')) {
            Schema::create('cycle_counts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('location_id')->constrained('wh_locations')->cascadeOnDelete();
                $table->integer('expected_count');
                $table->integer('actual_count');
                $table->integer('discrepancy');
                $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('counted_at');
                $table->text('notes')->nullable();
                $table->string('resolution_status', 20)->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                
                $table->index(['branch_id', 'counted_at']);
                $table->index(['location_id', 'counted_at']);
            });
        }

        // Add columns to wh_locations if they don't exist
        if (Schema::hasTable('wh_locations')) {
            Schema::table('wh_locations', function (Blueprint $table) {
                if (!Schema::hasColumn('wh_locations', 'name')) {
                    $table->string('name', 100)->nullable()->after('code');
                }
                if (!Schema::hasColumn('wh_locations', 'parent_id')) {
                    $table->foreignId('parent_id')->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('wh_locations', 'temperature_controlled')) {
                    $table->boolean('temperature_controlled')->default(false)->after('capacity');
                }
                if (!Schema::hasColumn('wh_locations', 'priority')) {
                    $table->integer('priority')->default(0)->after('temperature_controlled');
                }
                if (!Schema::hasColumn('wh_locations', 'last_counted_at')) {
                    $table->timestamp('last_counted_at')->nullable()->after('status');
                }
            });
        }

        // Add COD columns to shipments if they don't exist
        if (Schema::hasTable('shipments')) {
            Schema::table('shipments', function (Blueprint $table) {
                if (!Schema::hasColumn('shipments', 'cod_collected_amount')) {
                    $table->decimal('cod_collected_amount', 15, 2)->nullable()->after('cod_amount');
                }
                if (!Schema::hasColumn('shipments', 'cod_collected_at')) {
                    $table->timestamp('cod_collected_at')->nullable()->after('cod_collected_amount');
                }
                if (!Schema::hasColumn('shipments', 'cod_collection_method')) {
                    $table->string('cod_collection_method', 50)->nullable()->after('cod_collected_at');
                }
                if (!Schema::hasColumn('shipments', 'cod_collection_notes')) {
                    $table->text('cod_collection_notes')->nullable()->after('cod_collection_method');
                }
                if (!Schema::hasColumn('shipments', 'cod_collected_by')) {
                    $table->foreignId('cod_collected_by')->nullable()->after('cod_collection_notes');
                }
                if (!Schema::hasColumn('shipments', 'warehouse_location_id')) {
                    $table->foreignId('warehouse_location_id')->nullable()->after('dest_branch_id');
                }
                if (!Schema::hasColumn('shipments', 'receiving_condition')) {
                    $table->string('receiving_condition', 20)->nullable()->after('warehouse_location_id');
                }
                if (!Schema::hasColumn('shipments', 'receiving_notes')) {
                    $table->text('receiving_notes')->nullable()->after('receiving_condition');
                }
                if (!Schema::hasColumn('shipments', 'received_at')) {
                    $table->timestamp('received_at')->nullable()->after('receiving_notes');
                }
                if (!Schema::hasColumn('shipments', 'received_by')) {
                    $table->foreignId('received_by')->nullable()->after('received_at');
                }
                if (!Schema::hasColumn('shipments', 'dispatched_at')) {
                    $table->timestamp('dispatched_at')->nullable()->after('received_by');
                }
                if (!Schema::hasColumn('shipments', 'dispatched_by')) {
                    $table->foreignId('dispatched_by')->nullable()->after('dispatched_at');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_counts');
        Schema::dropIfExists('cod_remittances');
        Schema::dropIfExists('branch_expenses');
    }
};
