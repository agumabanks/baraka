<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createAnalyticsMaterializedSnapshots();
        $this->createAnalyticsJobHistory();
        $this->createAnalyticsPerformanceMetrics();
        $this->createRealtimeAnalyticsCache();
        $this->createCapacityForecasts();
        $this->createAnalyticsAlerts();
        $this->ensureOptimizedIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_alerts');
        Schema::dropIfExists('capacity_forecasts');
        Schema::dropIfExists('realtime_analytics_cache');
        Schema::dropIfExists('analytics_performance_metrics');
        Schema::dropIfExists('analytics_job_history');
        Schema::dropIfExists('analytics_materialized_snapshots');

        $this->dropOptimizedIndexes();
    }

    private function createAnalyticsMaterializedSnapshots(): void
    {
        if (Schema::hasTable('analytics_materialized_snapshots')) {
            return;
        }

        Schema::create('analytics_materialized_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->date('snapshot_date');
            $table->integer('data_period_days')->default(30);
            $table->decimal('total_shipments', 10, 2)->default(0);
            $table->decimal('delivered_shipments', 10, 2)->default(0);
            $table->decimal('delivery_success_rate', 5, 2)->default(0);
            $table->decimal('on_time_delivery_rate', 5, 2)->default(0);
            $table->decimal('utilization_rate', 5, 2)->default(0);
            $table->decimal('capacity_efficiency', 5, 2)->default(0);
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->integer('active_workers')->default(0);
            $table->integer('current_workload')->default(0);
            $table->json('detailed_metrics')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'snapshot_date']);
            $table->unique(['branch_id', 'snapshot_date']);
        });
    }

    private function createAnalyticsJobHistory(): void
    {
        if (Schema::hasTable('analytics_job_history')) {
            return;
        }

        Schema::create('analytics_job_history', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->string('job_type');
            $table->integer('branch_count')->default(0);
            $table->integer('processed_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->decimal('execution_time_seconds', 10, 3);
            $table->enum('status', ['pending', 'running', 'completed', 'completed_with_errors', 'failed']);
            $table->timestamps();

            $table->index('job_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    private function createAnalyticsPerformanceMetrics(): void
    {
        if (Schema::hasTable('analytics_performance_metrics')) {
            return;
        }

        Schema::create('analytics_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('execution_time_ms', 10, 3);
            $table->integer('memory_usage_mb')->nullable();
            $table->integer('records_processed')->default(0);
            $table->decimal('cache_hit_rate', 5, 2)->default(0);
            $table->string('cache_key_pattern')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('measured_at');

            $table->index(['operation_type', 'measured_at']);
            $table->index('branch_id');
            $table->index('execution_time_ms');
        });
    }

    private function createRealtimeAnalyticsCache(): void
    {
        if (Schema::hasTable('realtime_analytics_cache')) {
            return;
        }

        Schema::create('realtime_analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('metric_type');
            $table->json('metric_data');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['branch_id', 'metric_type']);
            $table->index('expires_at');
        });
    }

    private function createCapacityForecasts(): void
    {
        if (Schema::hasTable('capacity_forecasts')) {
            return;
        }

        Schema::create('capacity_forecasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->date('forecast_date');
            $table->integer('forecast_days');
            $table->decimal('predicted_demand', 10, 2);
            $table->decimal('predicted_capacity', 10, 2);
            $table->decimal('capacity_gap', 10, 2);
            $table->decimal('confidence_level', 5, 2);
            $table->json('forecast_factors');
            $table->json('risk_factors')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'forecast_date']);
            $table->unique(['branch_id', 'forecast_date', 'forecast_days']);
        });
    }

    private function createAnalyticsAlerts(): void
    {
        if (Schema::hasTable('analytics_alerts')) {
            return;
        }

        Schema::create('analytics_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('alert_type');
            $table->string('severity');
            $table->string('title');
            $table->text('description');
            $table->json('metric_data');
            $table->json('recommended_actions')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'alert_type']);
            $table->index('severity');
            $table->index('acknowledged');
            $table->index('created_at');
        });
    }

    private function ensureOptimizedIndexes(): void
    {
        if (Schema::hasTable('shipments')) {
            if (!$this->indexExists('shipments', 'idx_shipments_analytics_optimized')) {
                Schema::table('shipments', function (Blueprint $table) {
                    $columns = Schema::hasColumn('shipments', 'branch_id')
                        ? ['branch_id', 'current_status', 'created_at']
                        : ['origin_branch_id', 'current_status', 'created_at'];

                    $table->index($columns, 'idx_shipments_analytics_optimized');
                });
            }

            if (Schema::hasColumn('shipments', 'expected_delivery_date')
                && !$this->indexExists('shipments', 'idx_shipments_delivery_analytics')) {
                Schema::table('shipments', function (Blueprint $table) {
                    $table->index(['current_status', 'delivered_at', 'expected_delivery_date'], 'idx_shipments_delivery_analytics');
                });
            }

            if (!$this->indexExists('shipments', 'idx_analytics_multi_branch')
                && (Schema::hasColumn('shipments', 'branch_id') || Schema::hasColumn('shipments', 'origin_branch_id'))
                && Schema::hasColumn('shipments', 'current_status')
                && Schema::hasColumn('shipments', 'created_at')) {
                Schema::table('shipments', function (Blueprint $table) {
                    if (Schema::hasColumn('shipments', 'branch_id') && Schema::hasColumn('shipments', 'delivered_at')) {
                        $table->index(['branch_id', 'current_status', 'created_at', 'delivered_at'], 'idx_analytics_multi_branch');
                        return;
                    }

                    $columns = ['origin_branch_id'];
                    if (Schema::hasColumn('shipments', 'dest_branch_id')) {
                        $columns[] = 'dest_branch_id';
                    }
                    $columns[] = 'current_status';
                    $columns[] = 'created_at';
                    if (Schema::hasColumn('shipments', 'delivered_at')) {
                        $columns[] = 'delivered_at';
                    }

                    $table->index($columns, 'idx_analytics_multi_branch');
                });
            }
        }

        if (Schema::hasTable('branch_workers')) {
            if (!$this->indexExists('branch_workers', 'idx_branch_workers_capacity')) {
                Schema::table('branch_workers', function (Blueprint $table) {
                    $table->index(['branch_id', 'role', 'status'], 'idx_branch_workers_capacity');
                });
            }

            if (!$this->indexExists('branch_workers', 'idx_capacity_utilization') && Schema::hasColumn('branch_workers', 'created_at')) {
                Schema::table('branch_workers', function (Blueprint $table) {
                    $table->index(['branch_id', 'status', 'role', 'created_at'], 'idx_capacity_utilization');
                });
            }
        }

        if (Schema::hasTable('branches')
            && Schema::hasColumn('branches', 'status')
            && !$this->indexExists('branches', 'idx_branch_performance_tracking')) {
            Schema::table('branches', function (Blueprint $table) {
                $columns = ['status'];
                if (Schema::hasColumn('branches', 'is_operational')) {
                    $columns[] = 'is_operational';
                }
                if (Schema::hasColumn('branches', 'hierarchy_level')) {
                    $columns[] = 'hierarchy_level';
                }

                if (count($columns) > 1) {
                    $table->index($columns, 'idx_branch_performance_tracking');
                }
            });
        }
    }

    private function dropOptimizedIndexes(): void
    {
        if (Schema::hasTable('shipments')) {
            if ($this->indexExists('shipments', 'idx_shipments_analytics_optimized')) {
                Schema::table('shipments', function (Blueprint $table) {
                    $table->dropIndex('idx_shipments_analytics_optimized');
                });
            }

            if ($this->indexExists('shipments', 'idx_shipments_delivery_analytics')) {
                Schema::table('shipments', function (Blueprint $table) {
                    $table->dropIndex('idx_shipments_delivery_analytics');
                });
            }

            if ($this->indexExists('shipments', 'idx_analytics_multi_branch')) {
                Schema::table('shipments', function (Blueprint $table) {
                    $table->dropIndex('idx_analytics_multi_branch');
                });
            }
        }

        if (Schema::hasTable('branch_workers')) {
            if ($this->indexExists('branch_workers', 'idx_branch_workers_capacity')) {
                Schema::table('branch_workers', function (Blueprint $table) {
                    $table->dropIndex('idx_branch_workers_capacity');
                });
            }

            if ($this->indexExists('branch_workers', 'idx_capacity_utilization')) {
                Schema::table('branch_workers', function (Blueprint $table) {
                    $table->dropIndex('idx_capacity_utilization');
                });
            }
        }

        if (Schema::hasTable('branches') && $this->indexExists('branches', 'idx_branch_performance_tracking')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropIndex('idx_branch_performance_tracking');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        $result = DB::select(
            'SELECT COUNT(1) AS found FROM information_schema.STATISTICS WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index]
        );

        return isset($result[0]) && (int) $result[0]->found > 0;
    }
};
