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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('hubs')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('planned_at');
            $table->string('status')->default('PLANNED'); // PLANNED, STARTED, COMPLETED
            $table->json('stops_sequence')->nullable(); // array of sscc codes
            $table->decimal('total_distance_km', 8, 2)->nullable();
            $table->decimal('estimated_duration_hours', 4, 2)->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'planned_at']);
            $table->index('status');
            $table->index('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
