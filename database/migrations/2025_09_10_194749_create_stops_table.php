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
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->onDelete('cascade');
            $table->string('sscc'); // GS1 SSCC code
            $table->integer('sequence');
            $table->string('status')->default('PENDING'); // PENDING, ARRIVED, COMPLETED, FAILED
            $table->timestamp('eta_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('geo_location')->nullable(); // {"lat": float, "lng": float}
            $table->timestamps();

            $table->index(['route_id', 'sequence']);
            $table->index('sscc');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops');
    }
};
