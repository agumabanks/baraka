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
        Schema::create('shipment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('unified_branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            // Indexes for performance
            $table->index('shipment_id');
            $table->index('branch_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('occurred_at');
            $table->index(['shipment_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_logs');
    }
};
