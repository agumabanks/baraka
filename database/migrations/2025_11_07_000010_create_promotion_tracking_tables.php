<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('promotion_event_logs');
        Schema::dropIfExists('promotion_ab_tests');
        Schema::dropIfExists('customer_promotion_preferences');
        Schema::dropIfExists('promotion_stacking_rules');
        Schema::dropIfExists('promotion_effectiveness_metrics');
        Schema::dropIfExists('customer_milestone_history');
        Schema::dropIfExists('promotion_code_generations');
        Schema::dropIfExists('customer_promotion_usage');

        // Customer promotion usage tracking
        Schema::create('customer_promotion_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('promotional_campaign_id')->constrained()->onDelete('cascade');
            $table->string('usage_type', 50); // 'single_use', 'recurring', 'milestone'
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('order_value', 10, 2);
            $table->json('order_details')->nullable();
            $table->timestamp('used_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('source_channel')->nullable(); // 'web', 'api', 'mobile', 'manual'
            
            $table->index(['customer_id', 'promotional_campaign_id'], 'customer_promo_usage_customer_campaign_idx');
            $table->index('used_at', 'customer_promo_usage_used_at_idx');
            $table->index(['source_channel', 'used_at'], 'customer_promo_usage_source_idx');
        });

        // Promotion code generation tracking
        Schema::create('promotion_code_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotional_campaign_id')->constrained()->onDelete('cascade');
            $table->string('generated_code', 50)->unique();
            $table->string('batch_id', 100);
            $table->string('generation_template', 100);
            $table->json('generation_constraints')->nullable();
            $table->integer('codes_generated')->default(0);
            $table->timestamp('generated_at');
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->index(['batch_id', 'generated_at'], 'promo_code_batch_idx');
            $table->index('generated_code', 'promo_code_generated_code_idx');
        });

        // Milestone achievements history
        Schema::create('customer_milestone_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('milestone_type', 50);
            $table->string('milestone_category', 50); // 'shipment_count', 'volume', 'revenue', 'tenure'
            $table->bigInteger('milestone_threshold');
            $table->bigInteger('current_value');
            $table->decimal('progress_percentage', 5, 2);
            $table->boolean('achieved')->default(false);
            $table->timestamp('achieved_at')->nullable();
            $table->json('reward_details')->nullable();
            $table->string('reward_status', 20)->default('pending'); // 'pending', 'sent', 'claimed', 'expired'
            $table->json('notification_sent')->nullable();
            
            $table->index(['customer_id', 'milestone_type'], 'milestone_history_customer_idx');
            $table->index(['milestone_category', 'achieved'], 'milestone_history_category_idx');
            $table->index('achieved_at', 'milestone_history_achieved_idx');
        });

        // Promotion effectiveness tracking
        Schema::create('promotion_effectiveness_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotional_campaign_id')->constrained()->onDelete('cascade');
            $table->string('metric_type', 50); // 'conversion_rate', 'avg_order_value', 'customer_retention', 'roi'
            $table->string('time_period', 20); // 'daily', 'weekly', 'monthly'
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('metric_value', 10, 4);
            $table->decimal('baseline_value', 10, 4)->nullable();
            $table->decimal('improvement_percentage', 5, 2)->nullable();
            $table->integer('total_uses')->default(0);
            $table->decimal('total_revenue_impact', 10, 2)->default(0);
            $table->json('segment_breakdown')->nullable(); // Breakdown by customer segment
            
            $table->index(['promotional_campaign_id', 'metric_type', 'period_start'], 'promo_effectiveness_campaign_idx');
            $table->index(['time_period', 'period_start'], 'promo_effectiveness_period_idx');
        });

        // Anti-stacking rules configuration
        Schema::create('promotion_stacking_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name', 100);
            $table->string('rule_type', 50); // 'percentage_cap', 'mutual_exclusion', 'tier_priority'
            $table->text('rule_description');
            $table->json('applicable_campaign_types')->nullable();
            $table->json('excluded_campaign_types')->nullable();
            $table->json('customer_eligibility_rules')->nullable();
            $table->json('stacking_conditions')->nullable();
            $table->decimal('maximum_stackable_discount', 5, 2)->nullable();
            $table->integer('priority_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'effective_from', 'effective_to'], 'promo_stack_rules_active_idx');
            $table->index('rule_type', 'promo_stack_rules_type_idx');
        });

        // Customer promotion preferences
        Schema::create('customer_promotion_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->json('preferred_campaign_types')->nullable(); // ['percentage', 'fixed_amount', 'free_shipping']
            $table->json('preferred_discount_ranges')->nullable(); // {'min': 5, 'max': 50}
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);
            $table->json('excluded_categories')->nullable(); // Categories customer doesn't want promotions for
            $table->json('custom_eligibility_criteria')->nullable();
            $table->timestamp('last_updated');
            
            $table->unique('customer_id');
            $table->index('last_updated', 'customer_promo_pref_updated_idx');
        });

        // Promotion A/B testing framework
        Schema::create('promotion_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_name', 100);
            $table->string('test_type', 50); // 'conversion_optimization', 'discount_amount', 'messaging'
            $table->json('test_variants'); // ['control', 'variant_a', 'variant_b']
            $table->json('traffic_allocation')->nullable(); // {'control': 50, 'variant_a': 30, 'variant_b': 20}
            $table->json('eligibility_criteria')->nullable();
            $table->string('success_metric', 50); // 'conversion_rate', 'avg_order_value', 'revenue'
            $table->integer('sample_size_target')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->json('results')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->index(['status', 'start_date'], 'promo_ab_status_idx');
            $table->index('test_type', 'promo_ab_type_idx');
        });

        // Promotion event logs
        Schema::create('promotion_event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50); // 'created', 'activated', 'used', 'expired', 'stacked'
            $table->foreignId('promotional_campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->json('event_data');
            $table->string('source', 50); // 'api', 'admin', 'automatic', 'customer'
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('event_timestamp');
            
            $table->index(['event_type', 'event_timestamp'], 'promo_event_type_idx');
            $table->index(['promotional_campaign_id', 'event_timestamp'], 'promo_event_campaign_idx');
            $table->index(['customer_id', 'event_timestamp'], 'promo_event_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_event_logs');
        Schema::dropIfExists('promotion_ab_tests');
        Schema::dropIfExists('customer_promotion_preferences');
        Schema::dropIfExists('promotion_stacking_rules');
        Schema::dropIfExists('promotion_effectiveness_metrics');
        Schema::dropIfExists('customer_milestone_history');
        Schema::dropIfExists('promotion_code_generations');
        Schema::dropIfExists('customer_promotion_usage');
    }
};