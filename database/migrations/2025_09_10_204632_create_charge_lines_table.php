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
        Schema::create('charge_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->string('charge_type'); // BASE, FUEL, DIM, ACCESSORIAL
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->json('metadata')->nullable(); // calculation details
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('charge_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_lines');
    }
};
