<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promotional_campaigns')) {
            Schema::create('promotional_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('promo_code', 50)->unique()->nullable();
                $table->enum('campaign_type', ['percentage', 'fixed_amount', 'free_shipping', 'tier_upgrade']);
                $table->decimal('value', 10, 2);
                $table->decimal('minimum_order_value', 10, 2)->nullable();
                $table->decimal('maximum_discount_amount', 10, 2)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_count')->default(0);
                $table->json('customer_eligibility')->nullable();
                $table->boolean('stacking_allowed')->default(false);
                $table->timestamp('effective_from');
                $table->timestamp('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['is_active', 'effective_from', 'effective_to'], 'idx_campaign_active_validity');
                $table->index('promo_code');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotional_campaigns');
    }
};
