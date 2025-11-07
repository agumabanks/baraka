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

        Schema::create('dimension_churn_factors', function (Blueprint $table) {
            $table->string('factor_key')->primary();
            $table->string('factor_name', 100);
            $table->string('factor_category', 50);
            $table->text('factor_description')->nullable();
            $table->decimal('weight_in_model', 8, 4);
            $table->boolean('is_predictive');
            $table->boolean('is_preventable');
            $table->string('typical_impact_range', 100)->nullable();
            $table->text('recommended_intervention')->nullable();
            $table->decimal('monitoring_threshold', 8, 4)->nullable();
            $table->string('factor_type', 50);
            $table->string('data_source', 100);
            $table->text('calculation_method')->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('factor_category');
            $table->index('factor_type');
            $table->index('is_predictive');
            $table->index('is_preventable');
            $table->index('is_active');
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::dropIfExists('dimension_churn_factors');
    }
};