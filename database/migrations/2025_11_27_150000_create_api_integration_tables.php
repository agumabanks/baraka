<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // API Keys for external integrations
        if (!Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('key', 64)->unique();
                $table->string('secret_hash'); // Hashed secret
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                
                $table->json('permissions')->nullable(); // ['shipments:read', 'shipments:write', etc.]
                $table->json('allowed_ips')->nullable();
                $table->integer('rate_limit_per_minute')->default(60);
                
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                
                $table->index('key');
                $table->index('is_active');
            });
        }

        // Webhook subscriptions
        if (!Schema::hasTable('webhook_subscriptions')) {
            Schema::create('webhook_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('url');
                $table->string('secret')->nullable(); // For signature verification
                
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                
                $table->json('events'); // ['shipment.created', 'shipment.delivered', etc.]
                $table->json('headers')->nullable(); // Custom headers to send
                
                $table->boolean('is_active')->default(true);
                $table->integer('retry_count')->default(3);
                $table->integer('timeout_seconds')->default(30);
                
                $table->integer('consecutive_failures')->default(0);
                $table->timestamp('disabled_at')->nullable();
                $table->timestamp('last_triggered_at')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            });
        }

        // Webhook delivery logs
        if (!Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscription_id');
                $table->string('event');
                $table->json('payload');
                
                $table->string('status')->default('pending'); // pending, success, failed
                $table->integer('attempts')->default(0);
                $table->integer('response_code')->nullable();
                $table->text('response_body')->nullable();
                $table->integer('response_time_ms')->nullable();
                $table->text('error_message')->nullable();
                
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('next_retry_at')->nullable();
                $table->timestamps();
                
                $table->foreign('subscription_id')->references('id')->on('webhook_subscriptions')->cascadeOnDelete();
                
                $table->index(['subscription_id', 'status']);
                $table->index('created_at');
            });
        }

        // API request logs
        if (!Schema::hasTable('api_request_logs')) {
            Schema::create('api_request_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('api_key_id')->nullable();
                $table->string('method', 10);
                $table->string('endpoint');
                $table->string('ip_address', 45)->nullable();
                $table->integer('response_code');
                $table->integer('response_time_ms');
                $table->json('request_params')->nullable();
                $table->timestamp('created_at');
                
                $table->foreign('api_key_id')->references('id')->on('api_keys')->nullOnDelete();
                
                $table->index(['api_key_id', 'created_at']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('api_keys');
    }
};
