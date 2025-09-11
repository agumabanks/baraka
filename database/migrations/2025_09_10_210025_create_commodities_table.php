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
        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->string('description');
            $table->decimal('quantity', 8, 2);
            $table->string('unit'); // PCS, KG, M3
            $table->decimal('unit_value', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('total_value', 10, 2);
            $table->string('hs_code')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->json('customs_info')->nullable(); // additional customs data
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('hs_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commodities');
    }
};
