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
        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('delivery_man')->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('timestamp');
            $table->decimal('accuracy', 8, 2)->nullable(); // GPS accuracy in meters
            $table->decimal('speed', 8, 2)->nullable(); // Speed in m/s
            $table->decimal('heading', 5, 2)->nullable(); // Direction in degrees
            $table->timestamps();

            $table->index(['driver_id', 'timestamp']);
            $table->index(['timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};