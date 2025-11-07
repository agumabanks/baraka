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

        Schema::create('dimension_sentiment_categories', function (Blueprint $table) {
            $table->string('category_key')->primary();
            $table->string('category_name', 100);
            $table->string('category_type', 50);
            $table->text('description')->nullable();
            $table->json('sentiment_score_range')->nullable();
            $table->json('emotion_tags')->nullable();
            $table->integer('response_priority')->default(5);
            $table->boolean('escalation_required')->default(false);
            $table->json('recommended_actions')->nullable();
            $table->integer('sla_response_time')->nullable();
            $table->decimal('nps_impact_score', 8, 4)->default(0);
            $table->string('category_group', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('category_type');
            $table->index('category_group');
            $table->index('escalation_required');
            $table->index('response_priority');
            $table->index('is_active');
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('dimension_sentiment_categories');
    }
};