<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to webhook_endpoints if they don't exist
        Schema::table('webhook_endpoints', function (Blueprint $table) {
            if (!Schema::hasColumn('webhook_endpoints', 'secret_key')) {
                // Migrate 'secret' to 'secret_key' by adding new column
                $table->string('secret_key')->nullable()->after('secret');
            }
            if (!Schema::hasColumn('webhook_endpoints', 'retry_policy')) {
                $table->json('retry_policy')->nullable()->after('events');
            }
            if (!Schema::hasColumn('webhook_endpoints', 'failure_count')) {
                $table->integer('failure_count')->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('webhook_endpoints', 'last_triggered_at')) {
                $table->timestamp('last_triggered_at')->nullable()->after('last_delivery_at');
            }
        });

        // Add missing columns to webhook_deliveries if they don't exist
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('webhook_deliveries', 'event_type')) {
                $table->string('event_type')->nullable()->after('event');
            }
            if (!Schema::hasColumn('webhook_deliveries', 'http_status')) {
                $table->integer('http_status')->nullable()->after('response_status');
            }
            if (!Schema::hasColumn('webhook_deliveries', 'next_retry_at')) {
                $table->timestamp('next_retry_at')->nullable()->after('attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('webhook_deliveries', 'next_retry_at')) {
                $table->dropColumn('next_retry_at');
            }
            if (Schema::hasColumn('webhook_deliveries', 'http_status')) {
                $table->dropColumn('http_status');
            }
            if (Schema::hasColumn('webhook_deliveries', 'event_type')) {
                $table->dropColumn('event_type');
            }
        });

        Schema::table('webhook_endpoints', function (Blueprint $table) {
            if (Schema::hasColumn('webhook_endpoints', 'last_triggered_at')) {
                $table->dropColumn('last_triggered_at');
            }
            if (Schema::hasColumn('webhook_endpoints', 'failure_count')) {
                $table->dropColumn('failure_count');
            }
            if (Schema::hasColumn('webhook_endpoints', 'retry_policy')) {
                $table->dropColumn('retry_policy');
            }
            if (Schema::hasColumn('webhook_endpoints', 'secret_key')) {
                $table->dropColumn('secret_key');
            }
        });
    }
};
