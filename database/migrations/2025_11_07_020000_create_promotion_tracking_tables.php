<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer Promotion Usage Table
        Schema::create('customer_promotion_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('promotional_campaign_id')->constrained('promotional_campaigns')->onDelete('cascade');
            $table->string('promo_code', 50);
            $table->string('order_id')->nullable(); // External order reference
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('order_value', 10, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->timestamp('used_at');
            $table->json('usage_context')->nullable(); // Context data about the usage
            $table->json('validation_result')->nullable(); // Result of promotion validation
            $table->timestamps();
            
            $table->index(['customer_id', 'used_at']);
            $table->index(['promotional_campaign_id', 'used_at']);
            $table->index('promo_code');
            $table->index('used_at');
        });

        // Promotion Effectiveness Metrics Table
        Schema::create('promotion_effectiveness_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotional_campaign_id')->constrained('promotional_campaigns')->onDelete('cascade');
            $table->date('metric_date');
            
            // Financial Metrics
            $table->decimal('roi_percentage', 8, 2)->nullable();
            $table->decimal('revenue_impact', 12, 2)->default(0);
            $table->decimal('cost_impact', 12, 2)->default(0);
            $table->decimal('acquisition_cost', 10, 2)->nullable();
            $table->decimal('lifetime_value_impact', 10, 2)->nullable();
            
            // Performance Metrics
            $table->decimal('conversion_rate', 8, 4)->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('unique_customers')->default(0);
            $table->decimal('customer_engagement_score', 5, 2)->nullable();
            $table->decimal('retention_rate', 8, 4)->nullable();
            
            // Market Metrics
            $table->decimal('competitive_position', 5, 2)->nullable();
            $table->decimal('market_share_impact', 8, 4)->nullable();
            
            // Measurement Metadata
            $table->string('data_source')->default('internal'); // internal, external, third_party
            $table->integer('measurement_period')->default(1); // days
            $table->decimal('confidence_level', 5, 2)->default(100.0);
            $table->timestamps();
            
            $table->unique(['promotional_campaign_id', 'metric_date']);
            $table->index(['metric_date', 'roi_percentage']);
            $table->index('roi_percentage');
        });

        // Customer Milestone Notifications Table
        Schema::create('customer_milestone_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_milestone_id')->constrained('customer_milestones')->onDelete('cascade');
            $table->enum('notification_type', [
                'email', 'sms', 'push', 'in_app', 'webhook'
            ]);
            $table->json('notification_data')->nullable();
            $table->enum('status', [
                'pending', 'sent', 'delivered', 'failed', 'bounced'
            ])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
            
            $table->index(['customer_id', 'status']);
            $table->index('status');
            $table->index('sent_at');
        });

        // Promotion Webhook Endpoints Table
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('secret', 100);
            $table->json('events'); // Subscribed event types
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('retry_attempts')->default(3);
            $table->integer('timeout_seconds')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('total_deliveries')->default(0);
            $table->integer('successful_deliveries')->default(0);
            $table->integer('failed_deliveries')->default(0);
            $table->timestamps();
            
            $table->index('active');
            $table->index('last_triggered_at');
        });

        // Webhook Delivery Logs Table
        Schema::create('webhook_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->string('webhook_id')->index();
            $table->string('event_type');
            $table->enum('status', ['success', 'failed', 'pending', 'retry'])->default('pending');
            $table->integer('status_code')->nullable();
            $table->longText('request_payload');
            $table->longText('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(1);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            
            $table->index(['webhook_id', 'status']);
            $table->index(['event_type', 'created_at']);
            $table->index('status');
        });

        // Customer Promotion Preferences Table
        Schema::create('customer_promotion_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);
            $table->json('preferred_campaign_types')->nullable();
            $table->string('notification_frequency')->default('immediate'); // immediate, daily, weekly
            $table->json('quiet_hours')->nullable(); // Configuration for quiet hours
            $table->json('milestone_preferences')->nullable(); // Custom milestone preferences
            $table->json('promotion_categories')->nullable(); // Preferred promotion categories
            $table->timestamps();
            
            $table->unique('customer_id');
        });

        // Promotion A/B Tests Table
        Schema::create('promotion_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_name');
            $table->text('description')->nullable();
            $table->enum('status', [
                'draft', 'active', 'paused', 'completed', 'cancelled'
            ])->default('draft');
            
            // Test Configuration
            $table->json('variants'); // Test variants configuration
            $table->json('eligibility_criteria'); // Customer eligibility rules
            $table->string('success_metric')->default('conversion_rate');
            $table->integer('duration_days')->default(14);
            $table->integer('traffic_split_percentage')->default(50); // For single variant tests
            
            // Test Results
            $table->json('results')->nullable(); // Test results data
            $table->decimal('confidence_level', 5, 2)->default(95.0);
            $table->boolean('statistically_significant')->default(false);
            $table->string('winner_variant')->nullable();
            $table->text('conclusion')->nullable();
            
            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('started_at');
        });

        // Promotion Notifications Log Table
        Schema::create('promotion_notifications_log', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type'); // milestone_celebration, promotion_expiry, roi_alert, etc.
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('promotional_campaign_id')->nullable()->constrained('promotional_campaigns')->onDelete('set null');
            
            // Notification Details
            $table->json('notification_data');
            $table->json('channels_used'); // ['email', 'sms', 'push']
            $table->enum('delivery_status', [
                'pending', 'sent', 'delivered', 'failed', 'bounced'
            ])->default('pending');
            
            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['customer_id', 'notification_type']);
            $table->index('delivery_status');
            $table->index('sent_at');
        });

        // Add indexes for performance optimization
        Schema::table('promotional_campaigns', function (Blueprint $table) {
            $table->index(['is_active', 'effective_from', 'effective_to'], 'promotional_campaigns_active_period_idx');
        });

        // Create composite indexes for common queries
        Schema::table('customer_promotion_usage', function (Blueprint $table) {
            $table->index(['customer_id', 'promotional_campaign_id', 'used_at'], 'usage_customer_campaign_date_idx');
            $table->index(['promo_code', 'used_at'], 'usage_code_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_notifications_log');
        Schema::dropIfExists('promotion_ab_tests');
        Schema::dropIfExists('customer_promotion_preferences');
        Schema::dropIfExists('webhook_delivery_logs');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('customer_milestone_notifications');
        Schema::dropIfExists('promotion_effectiveness_metrics');
        Schema::dropIfExists('customer_promotion_usage');
        
        // Drop composite indexes
        Schema::table('promotional_campaigns', function (Blueprint $table) {
            $table->dropIndex('promotional_campaigns_active_period_idx');
        });
        
        Schema::table('customer_promotion_usage', function (Blueprint $table) {
            $table->dropIndex('usage_customer_campaign_date_idx');
            $table->dropIndex('usage_code_date_idx');
        });
    }
};