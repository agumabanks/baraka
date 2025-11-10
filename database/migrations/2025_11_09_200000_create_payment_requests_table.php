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
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_manager_id')->constrained('branch_managers')->cascadeOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'paid', 'declined'])->default('pending');
            $table->string('requested_by')->nullable()->comment('Username or system source that created the request');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['branch_manager_id', 'status'], 'idx_payment_requests_branch_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
