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
        Schema::create('settlement_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('period'); // YYYY-MM
            $table->foreignId('branch_id')->constrained('hubs')->onDelete('cascade');
            $table->decimal('total_revenue', 12, 2);
            $table->decimal('total_costs', 12, 2);
            $table->decimal('net_settlement', 12, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('PENDING'); // PENDING, PROCESSED, PAID
            $table->timestamp('processed_at')->nullable();
            $table->json('breakdown')->nullable(); // detailed calculations
            $table->timestamps();

            $table->unique(['period', 'branch_id']);
            $table->index('status');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_cycles');
    }
};
