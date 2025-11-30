<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily metrics snapshots for historical analysis
        if (!Schema::hasTable('daily_metrics')) {
            Schema::create('daily_metrics', function (Blueprint $table) {
                $table->id();
                $table->date('date')->index();
                $table->unsignedBigInteger('branch_id')->nullable();
                
                // Shipment metrics
                $table->integer('shipments_created')->default(0);
                $table->integer('shipments_picked_up')->default(0);
                $table->integer('shipments_delivered')->default(0);
                $table->integer('shipments_cancelled')->default(0);
                $table->integer('shipments_returned')->default(0);
                $table->integer('shipments_in_transit')->default(0);
                
                // Performance metrics
                $table->decimal('on_time_delivery_rate', 5, 2)->nullable();
                $table->decimal('average_delivery_hours', 8, 2)->nullable();
                $table->decimal('first_attempt_delivery_rate', 5, 2)->nullable();
                
                // Financial metrics
                $table->decimal('total_revenue', 15, 2)->default(0);
                $table->decimal('cod_collected', 15, 2)->default(0);
                $table->decimal('average_shipment_value', 10, 2)->nullable();
                
                // Customer metrics
                $table->integer('new_customers')->default(0);
                $table->integer('active_customers')->default(0);
                $table->integer('customer_complaints')->default(0);
                
                // Driver metrics
                $table->integer('active_drivers')->default(0);
                $table->decimal('average_deliveries_per_driver', 8, 2)->nullable();
                
                $table->timestamps();
                
                $table->unique(['date', 'branch_id']);
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            });
        }

        // Saved reports configuration
        if (!Schema::hasTable('saved_reports')) {
            Schema::create('saved_reports', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type'); // shipment, financial, performance, customer
                $table->text('description')->nullable();
                $table->json('filters')->nullable();
                $table->json('columns')->nullable();
                $table->json('grouping')->nullable();
                $table->string('schedule')->nullable(); // daily, weekly, monthly
                $table->json('recipients')->nullable(); // email recipients
                $table->unsignedBigInteger('created_by');
                $table->boolean('is_public')->default(false);
                $table->timestamp('last_run_at')->nullable();
                $table->timestamps();
                
                $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        // Report execution history
        if (!Schema::hasTable('report_executions')) {
            Schema::create('report_executions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('saved_report_id')->nullable();
                $table->string('report_type');
                $table->json('parameters')->nullable();
                $table->unsignedBigInteger('executed_by')->nullable();
                $table->string('status')->default('pending'); // pending, running, completed, failed
                $table->string('file_path')->nullable();
                $table->string('file_format')->nullable(); // pdf, xlsx, csv
                $table->integer('row_count')->nullable();
                $table->integer('execution_time_ms')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                
                $table->foreign('saved_report_id')->references('id')->on('saved_reports')->nullOnDelete();
                $table->foreign('executed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Delivery predictions cache
        if (!Schema::hasTable('delivery_predictions')) {
            Schema::create('delivery_predictions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipment_id')->unique();
                $table->timestamp('predicted_delivery_at');
                $table->decimal('confidence_score', 5, 2); // 0-100
                $table->json('factors')->nullable(); // factors that influenced prediction
                $table->timestamp('actual_delivery_at')->nullable();
                $table->integer('prediction_error_minutes')->nullable();
                $table->timestamps();
                
                $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_predictions');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('saved_reports');
        Schema::dropIfExists('daily_metrics');
    }
};
