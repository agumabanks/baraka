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

        Schema::dropIfExists('fact_customer_churn_metrics');

        Schema::create('fact_customer_churn_metrics', function (Blueprint $table) {
            $table->bigIncrements('churn_key');
            $table->unsignedBigInteger('client_key');
            $table->integer('churn_date_key');
            $table->decimal('churn_probability', 8, 4);
            $table->decimal('risk_score', 8, 4);
            $table->decimal('retention_score', 8, 4);
            $table->integer('days_since_last_shipment');
            $table->integer('total_shipments_90_days');
            $table->integer('complaints_count_90_days');
            $table->integer('payment_delays_90_days');
            $table->decimal('credit_utilization', 8, 4);
            $table->json('churn_indicators')->nullable();
            $table->json('primary_churn_factors')->nullable();
            $table->json('secondary_churn_factors')->nullable();
            $table->date('predicted_churn_date')->nullable();
            $table->json('recommended_actions')->nullable();
            $table->string('model_version', 50);
            $table->decimal('confidence_level', 8, 4);
            
            // Foreign key constraints
            $table->foreign('client_key')->references('client_key')->on('dim_client')->onDelete('cascade');
            $table->foreign('churn_date_key')->references('date_key')->on('dim_time')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('client_key');
            $table->index('churn_date_key');
            $table->index('churn_probability');
            $table->index('risk_score');
            $table->index(['client_key', 'churn_date_key']);
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('fact_customer_churn_metrics');
    }
};