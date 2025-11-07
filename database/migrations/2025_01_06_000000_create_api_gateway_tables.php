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
        // API Versions table
        Schema::create('api_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deprecated')->default(false);
            $table->timestamp('deprecation_date')->nullable();
            $table->string('migrated_to_version')->nullable();
            $table->timestamps();
        });

        // API Routes table
        Schema::create('api_routes', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->json('methods');
            $table->string('target_service');
            $table->foreignId('version_id')->nullable()->constrained('api_versions')->onDelete('set null');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('timeout')->default(30);
            $table->integer('connect_timeout')->default(10);
            $table->string('auth_type')->default('none');
            $table->json('auth_config')->nullable();
            $table->json('rate_limit_config')->nullable();
            $table->json('transform_config')->nullable();
            $table->json('validation_config')->nullable();
            $table->boolean('load_balanced')->default(false);
            $table->json('target_services')->nullable();
            $table->string('load_balancing_strategy')->default('round_robin');
            $table->string('health_check_path')->nullable();
            $table->json('retry_config')->nullable();
            $table->json('cors_config')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Rate Limit Rules table
        Schema::create('rate_limit_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_route_id')->constrained('api_routes')->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // ip, user, api_key, endpoint, custom
            $table->integer('limit');
            $table->integer('window'); // in seconds
            $table->integer('burst_limit')->default(0);
            $table->string('identifier')->default('ip');
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        // API Gateway Logs table
        Schema::create('api_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // request, response, error
            $table->string('request_id');
            $table->json('data');
            $table->timestamps();
            
            $table->index(['type', 'created_at']);
            $table->index('request_id');
        });

        // API Gateway Metrics table
        Schema::create('api_gateway_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // counter, gauge
            $table->string('metric');
            $table->decimal('value', 15, 6);
            $table->json('tags')->nullable();
            $table->timestamps();
            
            $table->index(['metric', 'created_at']);
            $table->index(['type', 'metric']);
        });

        // API Gateway Raw Metrics table
        Schema::create('api_gateway_raw_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('data');
            $table->timestamps();
            
            $table->index(['type', 'created_at']);
        });

        // Rate Limit Breaches table
        Schema::create('api_rate_limit_breaches', function (Blueprint $table) {
            $table->id();
            $table->string('request_id');
            $table->string('client_ip');
            $table->string('route');
            $table->string('method');
            $table->text('user_agent')->nullable();
            $table->integer('limit_exceeded');
            $table->integer('requests_in_window');
            $table->json('rate_limit_config');
            $table->timestamps();
            
            $table->index(['client_ip', 'created_at']);
            $table->index('route');
        });

        // Performance Alerts table
        Schema::create('api_performance_alerts', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp');
            $table->string('request_id');
            $table->string('path');
            $table->string('method');
            $table->string('client_ip');
            $table->string('alert_type'); // slow_request, high_memory, etc.
            $table->integer('threshold');
            $table->integer('actual_value');
            $table->integer('processing_time');
            $table->integer('memory_usage');
            $table->timestamps();
            
            $table->index(['alert_type', 'created_at']);
            $table->index('path');
        });

        // Load Balanced Routes table
        Schema::create('api_load_balanced_routes', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('target_service');
            $table->boolean('is_healthy')->default(true);
            $table->integer('weight')->default(1);
            $table->integer('current_load')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['path', 'target_service']);
            $table->index('path');
        });

        // API Keys table (extended from existing)
        if (!Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('key')->unique();
                $table->text('description')->nullable();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('permissions')->nullable();
                $table->integer('rate_limit')->default(100);
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                
                $table->index(['key', 'is_active']);
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_load_balanced_routes');
        Schema::dropIfExists('api_performance_alerts');
        Schema::dropIfExists('api_rate_limit_breaches');
        Schema::dropIfExists('api_gateway_raw_metrics');
        Schema::dropIfExists('api_gateway_metrics');
        Schema::dropIfExists('api_gateway_logs');
        Schema::dropIfExists('rate_limit_rules');
        Schema::dropIfExists('api_routes');
        Schema::dropIfExists('api_versions');
        
        // Only drop api_keys table if we created it
        if (Schema::hasTable('api_keys')) {
            Schema::dropIfExists('api_keys');
        }
    }
};