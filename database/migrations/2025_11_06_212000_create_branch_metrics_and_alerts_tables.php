<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branch_metrics')) {
            Schema::create('branch_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->date('snapshot_date');
                $table->string('window', 20)->default('daily'); // daily, weekly, monthly
                $table->unsignedInteger('throughput_count')->default(0);
                $table->decimal('capacity_utilization', 6, 3)->default(0);
                $table->decimal('exception_rate', 6, 3)->default(0);
                $table->decimal('on_time_rate', 6, 3)->default(0);
                $table->decimal('average_processing_time_hours', 8, 2)->nullable();
                $table->decimal('on_time_target', 5, 2)->nullable();
                $table->unsignedInteger('alerts_triggered')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamp('calculated_at')->nullable();
                $table->timestamps();

                $table->unique(['branch_id', 'snapshot_date', 'window'], 'branch_metrics_snapshot_unique');
                $table->index(['snapshot_date', 'window']);
            });
        }

        if (! Schema::hasTable('branch_alerts')) {
            Schema::create('branch_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('alert_type', 60);
                $table->string('severity', 20)->default('medium');
                $table->string('status', 20)->default('OPEN');
                $table->string('title', 160);
                $table->text('message');
                $table->json('context')->nullable();
                $table->timestamp('triggered_at');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'status']);
                $table->index(['alert_type', 'status']);
                $table->index('triggered_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_alerts');
        Schema::dropIfExists('branch_metrics');
    }
};
