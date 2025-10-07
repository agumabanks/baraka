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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('origin_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('dest_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('assigned_worker_id')->nullable()->constrained('branch_workers')->nullOnDelete();
            $table->string('tracking_number')->unique();
            $table->enum('status', [
                'created',
                'ready_for_pickup',
                'in_transit',
                'arrived_at_hub',
                'out_for_delivery',
                'delivered',
                'exception',
                'cancelled',
            ])->default('created');
            // Legacy workflow columns retained for compatibility
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_level')->nullable();
            $table->string('incoterm')->nullable();
            $table->decimal('price_amount', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->enum('current_status', [
                'CREATED',
                'CONFIRMED',
                'ASSIGNED',
                'PICKED_UP',
                'IN_TRANSIT',
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'CANCELLED',
            ])->default('CREATED');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('expected_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('public_token')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['origin_branch_id', 'dest_branch_id']);
            $table->index('assigned_worker_id');
            $table->index('current_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
