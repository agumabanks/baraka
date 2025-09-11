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
        Schema::create('transport_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->enum('mode', ['AIR', 'ROAD']);
            $table->string('carrier')->nullable();
            $table->string('flight_number')->nullable(); // for air
            $table->string('vehicle_number')->nullable(); // for road
            $table->string('awb')->nullable(); // Air Waybill
            $table->string('cmr')->nullable(); // CMR consignment note
            $table->timestamp('depart_at')->nullable();
            $table->timestamp('arrive_at')->nullable();
            $table->string('status')->default('PLANNED'); // PLANNED, DEPARTED, ARRIVED
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'mode']);
            $table->index('status');
            $table->index('depart_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_legs');
    }
};
