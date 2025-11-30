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
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // 'branch', 'vehicle', 'warehouse_location'
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('maintenance_type'); // 'scheduled', 'emergency', 'repair', 'inspection'
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamp('scheduled_start_at');
            $table->timestamp('scheduled_end_at');
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->integer('capacity_impact_percent')->default(0); // 0-100, how much capacity is reduced
            $table->text('description');
            $table->text('notes')->nullable();
            $table->json('affected_services')->nullable(); // list of services affected
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes
            $table->index(['entity_type', 'entity_id']);
            $table->index(['branch_id', 'status']);
            $table->index(['scheduled_start_at', 'scheduled_end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
    }
};
