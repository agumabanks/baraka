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
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('origin_branch_id')->constrained('hubs')->onDelete('cascade');
            $table->foreignId('dest_branch_id')->constrained('hubs')->onDelete('cascade');
            $table->string('service_level')->default('STANDARD');
            $table->string('incoterm')->default('DAP'); // DAP/DDP
            $table->decimal('price_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('current_status', array_column(\App\Enums\ShipmentStatus::cases(), 'value'))->default(\App\Enums\ShipmentStatus::CREATED->value);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('metadata')->nullable(); // for future extensions
            $table->softDeletes();
            $table->timestamps();

            $table->index(['origin_branch_id', 'dest_branch_id']);
            $table->index('current_status');
            $table->index('created_at');
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
