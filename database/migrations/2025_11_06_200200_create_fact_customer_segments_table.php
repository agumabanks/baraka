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

        Schema::dropIfExists('fact_customer_segments');

        Schema::create('fact_customer_segments', function (Blueprint $table) {
            $table->bigIncrements('segment_key');
            $table->unsignedBigInteger('client_key');
            $table->integer('segment_date_key');
            $table->string('primary_segment', 100);
            $table->json('secondary_segments')->nullable();
            $table->string('volume_tier', 20);
            $table->string('profitability_tier', 20);
            $table->string('behavioral_segment', 100);
            $table->string('lifecycle_stage', 50);
            $table->decimal('rfm_score', 8, 4);
            $table->json('segmentation_criteria')->nullable();
            $table->decimal('value_score', 8, 4);
            $table->decimal('engagement_score', 8, 4);
            $table->decimal('loyalty_score', 8, 4);
            $table->decimal('growth_potential', 8, 4);
            $table->decimal('retention_risk', 8, 4);
            $table->json('upsell_opportunities')->nullable();
            $table->json('cross_sell_opportunities')->nullable();
            $table->string('preferred_communication_channel', 50);
            $table->json('segment_characteristics')->nullable();
            $table->json('segment_changes')->nullable();
            $table->string('model_version', 50);
            
            // Foreign key constraints
            $table->foreign('client_key')->references('client_key')->on('dim_client')->onDelete('cascade');
            $table->foreign('segment_date_key')->references('date_key')->on('dim_time')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('client_key');
            $table->index('segment_date_key');
            $table->index('primary_segment');
            $table->index('volume_tier');
            $table->index('profitability_tier');
            $table->index('lifecycle_stage');
            $table->index('rfm_score');
            $table->index('value_score');
            $table->index('engagement_score');
            $table->index(['client_key', 'segment_date_key']);
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('fact_customer_segments');
    }
};