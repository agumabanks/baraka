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

        Schema::create('fact_customer_sentiment', function (Blueprint $table) {
            $table->bigInteger('sentiment_key')->primary();
            $table->bigInteger('client_key');
            $table->bigInteger('ticket_key')->nullable();
            $table->integer('sentiment_date_key');
            $table->integer('nps_score');
            $table->decimal('sentiment_score', 8, 4);
            $table->decimal('confidence_level', 8, 4);
            $table->string('feedback_category', 100);
            $table->string('primary_emotion', 50);
            $table->decimal('emotion_intensity', 8, 4);
            $table->string('language_detected', 10)->default('en');
            $table->string('support_channel', 50);
            $table->string('ticket_status', 50);
            $table->decimal('resolution_time_hours', 8, 2)->nullable();
            $table->integer('customer_satisfaction_rating')->nullable();
            $table->json('sentiment_trend')->nullable();
            $table->json('feedback_keywords')->nullable();
            $table->string('model_version', 50);
            $table->json('analysis_metadata')->nullable();
            
            // Foreign key constraints
            $table->foreign('client_key')->references('client_key')->on('dimension_clients');
            $table->foreign('sentiment_date_key')->references('date_key')->on('dimension_dates');
            
            // Indexes for performance
            $table->index('client_key');
            $table->index('sentiment_date_key');
            $table->index('nps_score');
            $table->index('sentiment_score');
            $table->index('feedback_category');
            $table->index(['client_key', 'sentiment_date_key']);
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('fact_customer_sentiment');
    }
};