<?php

use App\Enums\Status;
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
        Schema::create('branch_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('unified_branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('business_name')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->json('cod_charges')->nullable(); // COD charge configuration
            $table->json('payment_info')->nullable(); // Payment method preferences
            $table->json('settlement_config')->nullable(); // Settlement preferences
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->enum('status', [Status::ACTIVE, Status::INACTIVE])->default(Status::ACTIVE);
            $table->timestamps();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['branch_id', 'user_id']);

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index('current_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_managers');
    }
};
