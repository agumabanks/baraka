<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('webhook_endpoints')) {
            Schema::table('webhook_endpoints', function (Blueprint $table) {
                if (!Schema::hasColumn('webhook_endpoints', 'secret_key')) {
                    $table->string('secret_key', 64)->nullable()->after('secret');
                }

                if (!Schema::hasColumn('webhook_endpoints', 'retry_policy')) {
                    $table->json('retry_policy')->nullable()->after('events');
                }

                if (!Schema::hasColumn('webhook_endpoints', 'active')) {
                    $table->boolean('active')->default(true)->after('is_active');
                }

                if (!Schema::hasColumn('webhook_endpoints', 'failure_count')) {
                    $table->unsignedInteger('failure_count')->default(0)->after('active');
                }

                if (!Schema::hasColumn('webhook_endpoints', 'last_triggered_at')) {
                    $table->timestamp('last_triggered_at')->nullable()->after('last_delivery_at');
                }
            });

            DB::table('webhook_endpoints')
                ->select('id')
                ->whereNull('secret_key')
                ->orderBy('id')
                ->chunkById(100, function ($endpoints): void {
                    foreach ($endpoints as $endpoint) {
                        DB::table('webhook_endpoints')
                            ->where('id', $endpoint->id)
                            ->update(['secret_key' => Str::random(32)]);
                    }
                });

            DB::table('webhook_endpoints')
                ->whereNull('retry_policy')
                ->update([
                    'retry_policy' => json_encode([
                        'max_attempts' => 5,
                        'backoff_multiplier' => 2,
                        'initial_delay' => 60,
                        'max_delay' => 3600,
                    ]),
                ]);

            DB::table('webhook_endpoints')
                ->whereNull('active')
                ->update(['active' => DB::raw('COALESCE(is_active, 1)')]);
        }

        if (Schema::hasTable('webhook_deliveries')) {
            Schema::table('webhook_deliveries', function (Blueprint $table) {
                if (!Schema::hasColumn('webhook_deliveries', 'event_type')) {
                    $table->string('event_type')->nullable()->after('webhook_endpoint_id');
                }

                if (!Schema::hasColumn('webhook_deliveries', 'response')) {
                    $table->json('response')->nullable()->after('payload');
                }

                if (!Schema::hasColumn('webhook_deliveries', 'http_status')) {
                    $table->integer('http_status')->nullable()->after('response');
                }

                if (!Schema::hasColumn('webhook_deliveries', 'next_retry_at')) {
                    $table->timestamp('next_retry_at')->nullable()->after('attempts');
                }

                if (!Schema::hasColumn('webhook_deliveries', 'failed_at')) {
                    $table->timestamp('failed_at')->nullable()->after('delivered_at');
                }
            });

            Schema::table('webhook_deliveries', function (Blueprint $table) {
                $table->index(['next_retry_at', 'failed_at'], 'webhook_deliveries_retry_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('webhook_deliveries')) {
            Schema::table('webhook_deliveries', function (Blueprint $table) {
                if (Schema::hasColumn('webhook_deliveries', 'event_type')) {
                    $table->dropColumn('event_type');
                }
                if (Schema::hasColumn('webhook_deliveries', 'response')) {
                    $table->dropColumn('response');
                }
                if (Schema::hasColumn('webhook_deliveries', 'http_status')) {
                    $table->dropColumn('http_status');
                }
                if (Schema::hasColumn('webhook_deliveries', 'next_retry_at')) {
                    $table->dropColumn('next_retry_at');
                }
                if (Schema::hasColumn('webhook_deliveries', 'failed_at')) {
                    $table->dropColumn('failed_at');
                }
            });
        }

        if (Schema::hasTable('webhook_endpoints')) {
            Schema::table('webhook_endpoints', function (Blueprint $table) {
                if (Schema::hasColumn('webhook_endpoints', 'secret_key')) {
                    $table->dropColumn('secret_key');
                }
                if (Schema::hasColumn('webhook_endpoints', 'retry_policy')) {
                    $table->dropColumn('retry_policy');
                }
                if (Schema::hasColumn('webhook_endpoints', 'active')) {
                    $table->dropColumn('active');
                }
                if (Schema::hasColumn('webhook_endpoints', 'failure_count')) {
                    $table->dropColumn('failure_count');
                }
                if (Schema::hasColumn('webhook_endpoints', 'last_triggered_at')) {
                    $table->dropColumn('last_triggered_at');
                }
            });
        }
    }
};
