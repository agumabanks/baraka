<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::create('dimension_customer_segments', function (Blueprint $table) {
            $table->string('segment_key')->primary();
            $table->string('segment_name', 100);
            $table->string('segment_type', 50);
            $table->text('segment_description')->nullable();
            $table->json('volume_criteria')->nullable();
            $table->json('profitability_criteria')->nullable();
            $table->json('behavioral_criteria')->nullable();
            $table->json('value_score_range')->nullable();
            $table->json('engagement_criteria')->nullable();
            $table->json('lifecycle_stage_criteria')->nullable();
            $table->json('retention_risk_range')->nullable();
            $table->decimal('growth_potential_score', 8, 4)->nullable();
            $table->json('targeting_criteria')->nullable();
            $table->json('marketing_messaging')->nullable();
            $table->json('retention_strategies')->nullable();
            $table->json('upsell_opportunities')->nullable();
            $table->json('cross_sell_opportunities')->nullable();
            $table->integer('priority_level')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('segment_type');
            $table->index('priority_level');
            $table->index('is_active');
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('dimension_customer_segments');
    }
};