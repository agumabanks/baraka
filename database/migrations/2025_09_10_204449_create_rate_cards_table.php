<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('origin_country');
            $table->string('dest_country');
            $table->json('zone_matrix'); // pricing by zone/distance
            $table->json('weight_rules'); // pricing by weight bands
            $table->json('dim_rules'); // DIM weight calculation
            $table->decimal('fuel_surcharge_percent', 5, 2)->default(0);
            $table->json('accessorials'); // additional fees
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['origin_country', 'dest_country']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_cards');
    }
};
